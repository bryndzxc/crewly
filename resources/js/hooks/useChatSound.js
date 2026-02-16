import { useCallback, useEffect, useMemo, useRef } from 'react';

export default function useChatSound(enabled) {
    const enabledRef = useRef(Boolean(enabled));
    const lastPlayedAtRef = useRef(0);
    const unlockedRef = useRef(false);
    const audioRef = useRef(null);
    const audioContextRef = useRef(null);

    useEffect(() => {
        enabledRef.current = Boolean(enabled);
    }, [enabled]);

    const audioUrl = useMemo(() => {
        // If you later add a real file, this will pick it up.
        return '/sounds/message.mp3';
    }, []);

    const unlock = useCallback(() => {
        if (unlockedRef.current) return;
        unlockedRef.current = true;

        try {
            if (typeof Audio !== 'undefined') {
                const a = new Audio(audioUrl);
                a.preload = 'auto';
                audioRef.current = a;
            }
        } catch {
            // ignore
        }

        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (Ctx) {
                const ctx = new Ctx();
                audioContextRef.current = ctx;
                if (ctx.state === 'suspended') {
                    ctx.resume().catch(() => {});
                }
            }
        } catch {
            // ignore
        }
    }, [audioUrl]);

    useEffect(() => {
        const onFirstInteraction = () => unlock();
        window.addEventListener('pointerdown', onFirstInteraction, { once: true });
        window.addEventListener('keydown', onFirstInteraction, { once: true });
        return () => {
            window.removeEventListener('pointerdown', onFirstInteraction);
            window.removeEventListener('keydown', onFirstInteraction);
        };
    }, [unlock]);

    const beepFallback = useCallback(() => {
        try {
            const ctx = audioContextRef.current;
            const Ctx = window.AudioContext || window.webkitAudioContext;
            const audioCtx = ctx || (Ctx ? new Ctx() : null);
            if (!audioCtx) return;
            audioContextRef.current = audioCtx;

            if (audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.value = 0.03;
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            const now = audioCtx.currentTime;
            osc.start(now);
            osc.stop(now + 0.08);
        } catch {
            // ignore
        }
    }, []);

    const play = useCallback(() => {
        if (!enabledRef.current) return;

        const now = Date.now();
        if (now - lastPlayedAtRef.current < 1000) return;
        lastPlayedAtRef.current = now;

        // Prefer file if available, otherwise beep.
        const a = audioRef.current;
        if (a) {
            try {
                a.currentTime = 0;
                const p = a.play();
                if (p && typeof p.catch === 'function') {
                    p.catch(() => beepFallback());
                }
                return;
            } catch {
                // fall through
            }
        }

        beepFallback();
    }, [beepFallback]);

    return { play, unlock };
}
