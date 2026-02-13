// Best-effort resume text extraction for client-side autofill.
// Keep parsing conservative: do not throw hard errors to the UI.

import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist';

let workerConfigured = false;
function ensurePdfWorker() {
    if (workerConfigured) return;

    try {
        // pdfjs-dist v4 ships an ESM worker.
        GlobalWorkerOptions.workerSrc = new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url).toString();
        workerConfigured = true;
    } catch {
        // Ignore; pdf.js can sometimes fallback.
    }
}

async function extractPdfText(file) {
    ensurePdfWorker();

    const data = await file.arrayBuffer();
    const pdf = await getDocument({ data }).promise;

    const pageTexts = [];
    const totalPages = Math.min(pdf.numPages || 0, 3);

    for (let pageNum = 1; pageNum <= totalPages; pageNum += 1) {
        const page = await pdf.getPage(pageNum);
        const content = await page.getTextContent();

        // Try to restore lines by grouping items by Y coordinate.
        const items = (content?.items ?? [])
            .map((item) => {
                const str = String(item?.str ?? '').trim();
                const transform = item?.transform;
                const x = Array.isArray(transform) ? Number(transform?.[4] ?? 0) : 0;
                const y = Array.isArray(transform) ? Number(transform?.[5] ?? 0) : 0;
                return { str, x, y };
            })
            .filter((it) => it.str !== '');

        // Sort top-to-bottom, left-to-right.
        items.sort((a, b) => {
            if (Math.abs(a.y - b.y) > 2) return b.y - a.y;
            return a.x - b.x;
        });

        const lines = [];
        let current = [];
        let lastY = null;
        let lastX = null;

        for (const it of items) {
            if (lastY === null) {
                lastY = it.y;
                current.push({ str: it.str, x: it.x });
                lastX = it.x;
                continue;
            }

            if (Math.abs(it.y - lastY) <= 2) {
                current.push({ str: it.str, x: it.x });
                lastX = it.x;
                continue;
            }

            lines.push(
                current
                    .sort((a, b) => a.x - b.x)
                    .reduce((acc, chunk, idx, arr) => {
                        if (idx === 0) return chunk.str;
                        const prev = arr[idx - 1];
                        const gap = Number(chunk.x) - Number(prev.x);
                        // Heuristic: add a space only for larger gaps.
                        return acc + (gap > 12 ? ' ' : '') + chunk.str;
                    }, '')
                    .replace(/\s+/g, ' ')
                    .trim()
            );

            current = [{ str: it.str, x: it.x }];
            lastY = it.y;
            lastX = it.x;
        }

        if (current.length) {
            lines.push(
                current
                    .sort((a, b) => a.x - b.x)
                    .reduce((acc, chunk, idx, arr) => {
                        if (idx === 0) return chunk.str;
                        const prev = arr[idx - 1];
                        const gap = Number(chunk.x) - Number(prev.x);
                        return acc + (gap > 12 ? ' ' : '') + chunk.str;
                    }, '')
                    .replace(/\s+/g, ' ')
                    .trim()
            );
        }

        // Keep page separators so downstream heuristics can focus on header lines.
        pageTexts.push(lines.filter(Boolean).join('\n'));
    }

    return pageTexts.join('\n');
}

async function extractDocxText(file) {
    const ab = await file.arrayBuffer();

    // Use the browser build.
    const mammoth = await import('mammoth/mammoth.browser');
    const result = await mammoth.extractRawText({ arrayBuffer: ab });
    return String(result?.value ?? '');
}

function pickEmail(text) {
    const match = String(text).match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
    return match ? match[0] : '';
}

function pickPhone(text) {
    // Very permissive; keeps digits + common separators.
    const match = String(text).match(/(\+?\d[\d\s().-]{8,}\d)/);
    if (!match) return '';

    const raw = match[1];
    const cleaned = raw.replace(/\s+/g, ' ').trim();

    // Guard against accidentally matching long numeric blocks.
    const digits = cleaned.replace(/\D/g, '');
    if (digits.length < 9 || digits.length > 15) return '';

    return cleaned;
}

function stripContactsFromLine(line, email, phone) {
    let out = String(line || '');
    if (email) {
        out = out.replaceAll(email, ' ');
    }
    if (phone) {
        // Remove common phone representations (raw and digits-only variants are too risky).
        out = out.replaceAll(phone, ' ');
    }

    // Remove separators frequently used on header lines.
    out = out.replace(/[|•·]+/g, ' ');
    out = out.replace(/\s+-\s+/g, ' ');
    out = out.replace(/\s{2,}/g, ' ').trim();

    // If a line contains both name and contact, contacts usually come after.
    // Try trimming after the first occurrence of '@' or a long digit block.
    const atIdx = out.indexOf('@');
    if (atIdx >= 0) out = out.slice(0, atIdx).trim();

    const digitRun = out.match(/\d{6,}/);
    if (digitRun?.index != null) out = out.slice(0, digitRun.index).trim();

    return out;
}

function isLikelyNameLine(line) {
    const l = String(line).trim();
    if (!l) return false;

    const lower = l.toLowerCase();
    if (lower.includes('@')) return false;
    if (lower.includes('http') || lower.includes('www.')) return false;
    if (lower.includes('linkedin') || lower.includes('github')) return false;
    if (/\d/.test(l)) return false;

    // Common headings / sections
    const rejectContains = [
        'resume',
        'curriculum vitae',
        'cv',
        'objective',
        'summary',
        'profile',
        'experience',
        'employment',
        'education',
        'skills',
        'projects',
        'certifications',
        'references',
        'contact',
    ];
    if (rejectContains.some((w) => lower.includes(w))) return false;

    // Common job-title-ish words we don't want to treat as a name line.
    const jobWords = ['engineer', 'developer', 'designer', 'manager', 'analyst', 'specialist', 'consultant', 'intern'];
    if (jobWords.some((w) => lower.includes(w))) return false;

    // Keep it short-ish.
    if (l.length < 4 || l.length > 60) return false;

    // Allow letters, spaces and basic punctuation.
    if (!/^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ .,'-]*$/.test(l)) return false;

    const wordCount = l.split(/\s+/).filter(Boolean).length;
    if (wordCount < 2 || wordCount > 6) return false;

    return true;
}

function parseNameFromLine(line) {
    const raw = String(line).replace(/\s+/g, ' ').trim();

    // Handle "J O H N   D O E" (letters split by spaces).
    const tokens = raw.split(' ').filter(Boolean);
    const allSingleLetters = tokens.length >= 6 && tokens.every((t) => t.length === 1 && /[A-Za-z]/.test(t));
    if (allSingleLetters) {
        // Guess split point in the middle.
        const half = Math.max(2, Math.floor(tokens.length / 2));
        const first = tokens.slice(0, half).join('');
        const last = tokens.slice(half).join('');
        return { first_name: first, last_name: last };
    }

    // Handle "LAST, FIRST M." style.
    if (raw.includes(',')) {
        const [lastRaw, firstRaw] = raw.split(',').map((s) => s.trim());
        const firstParts = String(firstRaw || '').split(/\s+/).filter(Boolean);
        return {
            first_name: firstParts[0] || '',
            last_name: lastRaw || '',
        };
    }

    const parts = raw.split(' ').filter(Boolean);
    const suffixes = new Set(['jr', 'sr', 'ii', 'iii', 'iv']);
    const lastPart = parts[parts.length - 1] || '';
    const maybeSuffix = lastPart.toLowerCase().replace('.', '');

    const coreParts = suffixes.has(maybeSuffix) ? parts.slice(0, -1) : parts;
    if (coreParts.length === 0) return { first_name: '', last_name: '' };
    if (coreParts.length === 1) return { first_name: coreParts[0], last_name: '' };

    return {
        first_name: coreParts[0],
        last_name: coreParts[coreParts.length - 1],
    };
}

function pickName(text, { email = '', phone = '' } = {}) {
    const lines = String(text)
        .split(/\r?\n/)
        .map((l) => l.trim())
        .filter(Boolean);

    // Prefer the very top of the resume.
    const head = lines.slice(0, 30);

    // Support "Name: John Doe" patterns.
    const labeled = head.find((l) => /^name\s*:\s*/i.test(l));
    if (labeled) {
        const after = labeled.replace(/^name\s*:\s*/i, '').trim();
        if (isLikelyNameLine(after)) return parseNameFromLine(after);
    }

    // First pass: use raw lines.
    let candidate = head.find((l) => isLikelyNameLine(l)) || '';

    // Second pass: strip contacts from lines like "John Doe | john@x.com | +63...".
    if (!candidate) {
        candidate =
            head
                .map((l) => stripContactsFromLine(l, email, phone))
                .find((l) => isLikelyNameLine(l)) ||
            '';
    }

    if (!candidate) return { first_name: '', last_name: '' };

    return parseNameFromLine(candidate);
}

export async function extractResumeFields(file) {
    if (!file) return { first_name: '', last_name: '', email: '', mobile_number: '' };

    const name = String(file?.name ?? '').toLowerCase();
    const type = String(file?.type ?? '').toLowerCase();

    let text = '';

    try {
        if (type.includes('pdf') || name.endsWith('.pdf')) {
            text = await extractPdfText(file);
        } else if (name.endsWith('.docx') || type.includes('officedocument.wordprocessingml')) {
            text = await extractDocxText(file);
        } else {
            // .doc is not reliably parseable client-side.
            text = '';
        }
    } catch {
        text = '';
    }

    if (!text) return { first_name: '', last_name: '', email: '', mobile_number: '' };

    const email = pickEmail(text);
    const mobile_number = pickPhone(text);

    // Name is usually in the header; restrict to early lines for fewer false positives.
    const headerText = String(text)
        .split(/\r?\n/)
        .slice(0, 40)
        .join('\n');

    let { first_name, last_name } = pickName(headerText, { email, phone: mobile_number });

    // Fallback 1: infer from email local-part (john.doe@... -> John / Doe).
    if (!first_name && !last_name && email) {
        const local = String(email).split('@')[0] || '';
        const parts = local
            .split(/[._-]+/)
            .map((p) => p.trim())
            .filter((p) => p.length >= 2 && /^[a-z]+$/i.test(p));

        if (parts.length >= 2) {
            first_name = parts[0];
            last_name = parts[parts.length - 1];
        }
    }

    // Fallback 2: infer from filename (John Doe Resume.pdf -> John / Doe).
    if (!first_name && !last_name) {
        const base = String(file?.name ?? '')
            .replace(/\.[^/.]+$/, '')
            .replace(/resume|cv/gi, ' ')
            .replace(/[._-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        const parts = base.split(' ').filter(Boolean);
        if (parts.length >= 2) {
            first_name = parts[0];
            last_name = parts[parts.length - 1];
        }
    }

    // Normalize casing a bit for nicer autofill.
    const cap = (s) => {
        const v = String(s || '').trim();
        if (!v) return '';
        return v
            .split(/\s+/)
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
            .join(' ');
    };

    first_name = cap(first_name);
    last_name = cap(last_name);

    return {
        first_name,
        last_name,
        email,
        mobile_number,
    };
}
