import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useRef, useState } from 'react';

function formatTime(iso) {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        return d.toLocaleString();
    } catch {
        return String(iso);
    }
}

function isNearBottom(el, thresholdPx = 160) {
    if (!el) return true;
    const remaining = el.scrollHeight - el.scrollTop - el.clientHeight;
    return remaining < thresholdPx;
}

export default function ChatIndex({ auth, crewly, conversations = [], selectedConversation, messages: initialMessages = [], hasMore = false, dmUsers = [] }) {
    const [query, setQuery] = useState('');
    const [items, setItems] = useState(Array.isArray(conversations) ? conversations : []);
    const [active, setActive] = useState(selectedConversation || null);
    const [messages, setMessages] = useState(Array.isArray(initialMessages) ? initialMessages : []);
    const [hasMoreState, setHasMoreState] = useState(Boolean(hasMore));
    const [loading, setLoading] = useState(false);
    const [composer, setComposer] = useState('');
    const [showDmModal, setShowDmModal] = useState(false);
    const [dmSearch, setDmSearch] = useState('');
    const [startingDm, setStartingDm] = useState(false);

    const demoEmail = String((crewly?.demo_email ?? 'demo@crewly.test') || '').toLowerCase();
    const myEmail = String(auth?.user?.email || '').toLowerCase();
    const isDemo = demoEmail !== '' && myEmail === demoEmail;

    const listRef = useRef(null);

    const [onlineUsers, setOnlineUsers] = useState([]);
    const [typingUsers, setTypingUsers] = useState([]);
    const presenceChannelRef = useRef(null);
    const typingPruneTimerRef = useRef(null);
    const lastTypingWhisperAtRef = useRef(0);
    const stopTypingTimerRef = useRef(null);

    useEffect(() => {
        setItems(Array.isArray(conversations) ? conversations : []);
    }, [conversations]);

    useEffect(() => {
        setActive(selectedConversation || null);
        setMessages(Array.isArray(initialMessages) ? initialMessages : []);
        setHasMoreState(Boolean(hasMore));
    }, [selectedConversation?.id, hasMore]);

    const channels = useMemo(() => items.filter((c) => c.type === 'CHANNEL'), [items]);
    const dms = useMemo(() => items.filter((c) => c.type === 'DM'), [items]);

    const filteredChannels = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return channels;
        return channels.filter((c) => String(c.name || '').toLowerCase().includes(q));
    }, [channels, query]);

    const filteredDms = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return dms;
        return dms.filter((c) => String(c.name || '').toLowerCase().includes(q));
    }, [dms, query]);

    const dmCandidates = useMemo(() => {
        const q = dmSearch.trim().toLowerCase();
        const base = Array.isArray(dmUsers) ? dmUsers : [];
        if (!q) return base;
        return base.filter((u) => {
            const name = String(u.name || '').toLowerCase();
            const role = String(u.role || '').toLowerCase();
            return name.includes(q) || role.includes(q);
        });
    }, [dmUsers, dmSearch]);

    const scrollToBottom = () => {
        const el = listRef.current;
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    };

    useEffect(() => {
        // initial autoscroll
        requestAnimationFrame(() => scrollToBottom());
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active?.id]);

    const markRead = async (conversationId) => {
        try {
            await axios.patch(route('chat.conversations.read', conversationId));
            setItems((prev) => prev.map((c) => (c.id === conversationId ? { ...c, unread: false } : c)));
        } catch {
            // ignore
        }
    };

    const loadConversation = async (conversationId, { preserveScroll = false } = {}) => {
        if (!conversationId) return;
        setLoading(true);
        try {
            const res = await axios.get(route('chat.conversations.show', conversationId));
            const payload = res?.data;
            setActive(payload?.conversation || null);
            setMessages(Array.isArray(payload?.messages) ? payload.messages : []);
            setHasMoreState(Boolean(payload?.has_more));
            setItems((prev) => prev.map((c) => (c.id === conversationId ? { ...c, unread: false } : c)));
            await markRead(conversationId);
            if (!preserveScroll) {
                requestAnimationFrame(() => scrollToBottom());
            }
        } finally {
            setLoading(false);
        }
    };

    const loadMore = async () => {
        if (!active?.id || messages.length === 0) return;
        const beforeId = messages[0]?.id;
        if (!beforeId) return;
        setLoading(true);
        try {
            const res = await axios.get(route('chat.conversations.show', active.id), { params: { before_id: beforeId } });
            const payload = res?.data;
            const next = Array.isArray(payload?.messages) ? payload.messages : [];
            setHasMoreState(Boolean(payload?.has_more));
            if (next.length > 0) {
                setMessages((prev) => [...next, ...prev]);
            }
        } finally {
            setLoading(false);
        }
    };

    const onSend = async () => {
        if (!active?.id) return;
        if (isDemo) return;
        const body = composer.trim();
        if (!body) return;
        setComposer('');
        try {
            try {
                presenceChannelRef.current?.whisper('typing', { user_id: auth?.user?.id, stopped: true });
            } catch {
                // ignore
            }
            const res = await axios.post(route('chat.messages.store', active.id), { body });
            const msg = res?.data?.message;
            if (msg) {
                setMessages((prev) => [...prev, msg]);
                setItems((prev) =>
                    prev.map((c) => (c.id === active.id ? { ...c, last_message_at: msg.created_at, unread: false } : c))
                );
                await markRead(active.id);
                // After sending your own message, keep the UI pinned to the newest message.
                requestAnimationFrame(() => scrollToBottom());
            }
        } catch {
            // restore composer on failure
            setComposer(body);
        }
    };

    const displayUserLabel = (u) => {
        const role = String(u?.role || '').trim();
        if (role) return role.toUpperCase();
        const name = String(u?.name || '').trim();
        return name || 'User';
    };

    const pruneTypingUsers = () => {
        const now = Date.now();
        setTypingUsers((prev) => prev.filter((u) => now - (u.lastSeenAt || 0) < 3000));
    };

    const emitTyping = (nextValue) => {
        const channel = presenceChannelRef.current;
        if (!channel || !active?.id) return;

        const myId = Number(auth?.user?.id);
        if (!myId) return;

        const isEmpty = !String(nextValue || '').trim();

        if (isEmpty) {
            try {
                channel.whisper('typing', { user_id: myId, stopped: true });
            } catch {
                // ignore
            }
            return;
        }

        const now = Date.now();
        if (now - lastTypingWhisperAtRef.current < 800) return;
        lastTypingWhisperAtRef.current = now;

        try {
            channel.whisper('typing', {
                user_id: myId,
                name: auth?.user?.name,
                role: auth?.user?.role,
                conversation_id: active.id,
            });
        } catch {
            // ignore
        }
    };

    const startDm = async (userId) => {
        setStartingDm(true);
        try {
            const res = await axios.post(route('chat.dm'), { user_id: userId });
            const conversationId = res?.data?.conversation_id;
            if (!conversationId) return;

            // ensure it's on the left list
            const existing = items.find((c) => c.id === conversationId);
            if (!existing) {
                const other = (Array.isArray(dmUsers) ? dmUsers : []).find((u) => u.id === userId);
                setItems((prev) => [
                    ...prev,
                    {
                        id: conversationId,
                        type: 'DM',
                        slug: null,
                        name: other?.name || 'Direct Message',
                        last_message_at: null,
                        unread: false,
                    },
                ]);
            }

            setShowDmModal(false);
            setDmSearch('');
            await loadConversation(conversationId);
        } finally {
            setStartingDm(false);
        }
    };

    // Realtime listener for all sidebar conversations (MVP)
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;

        const subscriptions = [];
        const myId = Number(auth?.user?.id);

        // Subscribe to all listed conversations to detect off-screen new messages.
        // MVP: only conversations from the sidebar.
        for (const c of items) {
            const conversationId = c?.id;
            if (!conversationId) continue;
            const channelName = `conversation.${conversationId}`;
            const channel = echo.private(channelName);
            const handler = (e) => {
                const msg = e?.message;
                if (!msg) return;
                const senderId = Number(msg?.sender?.id);
                if (senderId === myId) return;

                const conversationIdFromEvent = Number(msg?.conversation_id);
                const isActive = Number(active?.id) === conversationIdFromEvent;

                if (isActive) {
                    const el = listRef.current;
                    const shouldScroll = isNearBottom(el);

                    setMessages((prev) => {
                        if (prev.some((m) => m.id === msg.id)) return prev;
                        return [...prev, msg];
                    });

                    setItems((prev) =>
                        prev.map((row) =>
                            row.id === conversationIdFromEvent
                                ? { ...row, last_message_at: msg.created_at, unread: false }
                                : row
                        )
                    );

                    markRead(conversationIdFromEvent);
                    if (shouldScroll) requestAnimationFrame(() => scrollToBottom());
                    return;
                }

                // Non-active conversation: mark unread.
                setItems((prev) =>
                    prev.map((row) =>
                        row.id === conversationIdFromEvent
                            ? { ...row, unread: true, last_message_at: msg.created_at }
                            : row
                    )
                );
            };

            channel.listen('.MessageSent', handler);
            subscriptions.push({ channelName, channel, handler });
        }

        return () => {
            try {
                for (const s of subscriptions) {
                    s.channel.stopListening('.MessageSent');
                    echo.leave(s.channelName);
                }
            } catch {
                // ignore
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [items.map((c) => c.id).join(','), active?.id, auth?.user?.id]);

    // Presence + typing (active conversation only)
    useEffect(() => {
        const echo = window.Echo;
        const conversationId = Number(active?.id);
        const myId = Number(auth?.user?.id);
        if (!echo || !conversationId || !myId) {
            setOnlineUsers([]);
            setTypingUsers([]);
            return;
        }

        let channel;
        try {
            channel = echo.join(`conversation.${conversationId}`);
        } catch {
            channel = null;
        }

        presenceChannelRef.current = channel;
        setTypingUsers([]);

        if (typingPruneTimerRef.current) window.clearInterval(typingPruneTimerRef.current);
        typingPruneTimerRef.current = window.setInterval(() => pruneTypingUsers(), 1000);

        if (!channel) return;

        channel
            .here((users) => {
                setOnlineUsers(Array.isArray(users) ? users : []);
            })
            .joining((user) => {
                setOnlineUsers((prev) => {
                    const list = Array.isArray(prev) ? prev : [];
                    if (!user?.id || list.some((u) => Number(u.id) === Number(user.id))) return list;
                    return [...list, user];
                });
            })
            .leaving((user) => {
                setOnlineUsers((prev) => (Array.isArray(prev) ? prev.filter((u) => Number(u.id) !== Number(user?.id)) : []));
                setTypingUsers((prev) => (Array.isArray(prev) ? prev.filter((u) => Number(u.id) !== Number(user?.id)) : []));
            })
            .listenForWhisper('typing', (payload) => {
                const fromId = Number(payload?.user_id);
                if (!fromId || fromId === myId) return;

                if (payload?.stopped === true) {
                    setTypingUsers((prev) => prev.filter((u) => Number(u.id) !== fromId));
                    return;
                }

                const label = payload?.role ? String(payload.role).toUpperCase() : String(payload?.name || '').trim();
                setTypingUsers((prev) => {
                    const next = Array.isArray(prev) ? [...prev] : [];
                    const idx = next.findIndex((u) => Number(u.id) === fromId);
                    const entry = { id: fromId, label: label || 'User', lastSeenAt: Date.now() };
                    if (idx === -1) return [...next, entry];
                    next[idx] = entry;
                    return next;
                });
            });

        return () => {
            try {
                echo.leave(`presence-conversation.${conversationId}`);
                echo.leave(`conversation.${conversationId}`);
            } catch {
                // ignore
            }
            presenceChannelRef.current = null;
            setOnlineUsers([]);
            setTypingUsers([]);
            if (typingPruneTimerRef.current) window.clearInterval(typingPruneTimerRef.current);
            typingPruneTimerRef.current = null;
            if (stopTypingTimerRef.current) window.clearTimeout(stopTypingTimerRef.current);
            stopTypingTimerRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active?.id, auth?.user?.id]);

    const headerTitle = active?.type === 'CHANNEL' ? `# ${active?.name || ''}` : active?.name || 'Chat';
    const participantText = useMemo(() => {
        const ps = Array.isArray(active?.participants) ? active.participants : [];
        if (!ps.length) return '';
        return ps.map((p) => p.name).join(', ');
    }, [active?.participants]);

    const presenceText = useMemo(() => {
        if (!active?.id) return '';
        const list = Array.isArray(onlineUsers) ? onlineUsers : [];
        const myId = Number(auth?.user?.id);

        if (active?.type === 'DM') {
            const ps = Array.isArray(active?.participants) ? active.participants : [];
            const other = ps.find((p) => Number(p?.id) !== myId);
            if (!other?.id) return '';
            const isOtherOnline = list.some((u) => Number(u?.id) === Number(other.id));
            return isOtherOnline ? 'Online' : 'Offline';
        }

        const onlineCount = list.length;
        return onlineCount > 0 ? `${onlineCount} online` : '';
    }, [active?.id, active?.type, active?.participants, onlineUsers, auth?.user?.id]);

    const typingText = useMemo(() => {
        const list = Array.isArray(typingUsers) ? typingUsers : [];
        if (list.length === 0) return '';
        if (list.length === 1) return `${list[0].label} is typing…`;
        return `${list[0].label} and ${list.length - 1} other${list.length - 1 === 1 ? '' : 's'} are typing…`;
    }, [typingUsers]);

    return (
        <AuthenticatedLayout user={auth.user} header="Chat" contentClassName="max-w-none">
            <Head title="Chat" />

            <Card className="p-0 overflow-hidden">
                <div className="h-[70vh] min-h-[520px] grid grid-cols-12 bg-white">
                    {/* Left sidebar */}
                    <div className="col-span-12 md:col-span-4 lg:col-span-3 border-r border-slate-200 bg-slate-50 flex flex-col min-h-0">
                        <div className="p-4 border-b border-slate-200 bg-white/70">
                            <input
                                type="text"
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Search"
                                className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-400 focus:ring-amber-400"
                            />
                            <div className="mt-3 flex justify-between">
                                <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rooms</div>
                                <button
                                    type="button"
                                    onClick={() => setShowDmModal(true)}
                                    className="text-xs font-semibold text-amber-700 hover:text-amber-800"
                                >
                                    New Message
                                </button>
                            </div>
                        </div>

                        <div className="p-3 space-y-4 overflow-auto flex-1 min-h-0">
                            <div>
                                <div className="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Channels</div>
                                <div className="mt-2 space-y-1">
                                    {filteredChannels.map((c) => {
                                        const activeRow = active?.id === c.id;
                                        return (
                                            <button
                                                key={c.id}
                                                type="button"
                                                onClick={() => loadConversation(c.id)}
                                                className={
                                                    'w-full flex items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-medium transition ' +
                                                    (activeRow
                                                        ? 'bg-amber-50 text-slate-900 ring-1 ring-amber-200'
                                                        : 'text-slate-700 hover:bg-amber-50/60 hover:text-slate-900')
                                                }
                                            >
                                                <span className="text-slate-400">#</span>
                                                <span className="truncate flex-1">{c.name}</span>
                                                {c.unread && <span className="h-2 w-2 rounded-full bg-amber-500" />}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <div className="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Direct Messages</div>
                                <div className="mt-2 space-y-1">
                                    {filteredDms.length === 0 ? (
                                        <div className="px-3 py-2 text-sm text-slate-500">No DMs yet.</div>
                                    ) : (
                                        filteredDms.map((c) => {
                                            const activeRow = active?.id === c.id;
                                            return (
                                                <button
                                                    key={c.id}
                                                    type="button"
                                                    onClick={() => loadConversation(c.id)}
                                                    className={
                                                        'w-full flex items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-medium transition ' +
                                                        (activeRow
                                                            ? 'bg-amber-50 text-slate-900 ring-1 ring-amber-200'
                                                            : 'text-slate-700 hover:bg-amber-50/60 hover:text-slate-900')
                                                    }
                                                >
                                                    <span className="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                                        {String(c.name || 'D').slice(0, 1).toUpperCase()}
                                                    </span>
                                                    <span className="truncate flex-1">{c.name}</span>
                                                    {c.unread && (
                                                        <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                                            Unread
                                                        </span>
                                                    )}
                                                </button>
                                            );
                                        })
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right panel */}
                    <div className="col-span-12 md:col-span-8 lg:col-span-9 flex flex-col min-w-0 min-h-0">
                        <div className="px-5 py-4 border-b border-slate-200 bg-white flex items-center justify-between gap-4">
                            <div className="min-w-0">
                                <div className="truncate text-sm font-semibold text-slate-900">{headerTitle}</div>
                                {!!participantText && <div className="truncate text-xs text-slate-500">{participantText}</div>}
                                {!!presenceText && <div className="truncate text-xs text-slate-500">{presenceText}</div>}
                                {!!typingText && <div className="truncate text-xs text-amber-700">{typingText}</div>}
                            </div>
                            <div className="flex items-center gap-4">{loading && <div className="text-xs text-slate-500">Loading…</div>}</div>
                        </div>

                        <div className="flex-1 min-h-0 bg-white flex flex-col">
                                <div className="px-5 pt-4">
                                    {hasMoreState && (
                                        <button
                                            type="button"
                                            onClick={loadMore}
                                            className="text-sm font-semibold text-amber-700 hover:text-amber-800"
                                        >
                                            Load more
                                        </button>
                                    )}
                                </div>

                                <div ref={listRef} className="flex-1 min-h-0 overflow-auto px-5 py-4 space-y-3">
                                    {!active ? (
                                        <div className="h-full flex items-center justify-center text-slate-500">Select a room to start.</div>
                                    ) : messages.length === 0 ? (
                                        <div className="text-slate-500">No messages yet.</div>
                                    ) : (
                                        messages.map((m) => (
                                            <div key={m.id} className="flex items-start gap-3">
                                                <span className="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                                                    {String(m.sender?.name || 'U').slice(0, 1).toUpperCase()}
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-baseline gap-2">
                                                        <div className="truncate text-sm font-semibold text-slate-900">{m.sender?.name || 'User'}</div>
                                                        <div className="text-xs text-slate-500">{formatTime(m.created_at)}</div>
                                                    </div>
                                                    <div className="mt-0.5 whitespace-pre-wrap break-words text-sm text-slate-800">{m.body}</div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>

                                <div className="border-t border-slate-200 bg-slate-50 px-5 py-4">
                                    <div className="flex items-end gap-3">
                                        <textarea
                                            value={composer}
                                            onChange={(e) => {
                                                if (isDemo) return;
                                                const next = e.target.value;
                                                setComposer(next);
                                                emitTyping(next);

                                                if (stopTypingTimerRef.current) window.clearTimeout(stopTypingTimerRef.current);
                                                stopTypingTimerRef.current = window.setTimeout(() => {
                                                    try {
                                                        const myId = Number(auth?.user?.id);
                                                        presenceChannelRef.current?.whisper('typing', { user_id: myId, stopped: true });
                                                    } catch {
                                                        // ignore
                                                    }
                                                }, 1400);
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter' && !e.shiftKey) {
                                                    e.preventDefault();
                                                    onSend();
                                                }
                                            }}
                                            placeholder={active ? 'Message…' : 'Select a conversation…'}
                                            disabled={!active || isDemo}
                                            rows={2}
                                            className="flex-1 resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-400 focus:ring-amber-400 disabled:bg-slate-100"
                                        />
                                        <PrimaryButton type="button" disabled={!active || isDemo || !composer.trim()} onClick={onSend}>
                                            Send
                                        </PrimaryButton>
                                    </div>
                                    <div className="mt-2 text-xs text-slate-500">
                                        {isDemo ? 'Demo account: sending is disabled.' : 'Enter to send • Shift+Enter for new line'}
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </Card>

            <Modal show={showDmModal} onClose={() => setShowDmModal(false)} maxWidth="lg">
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">New Message</div>
                    <div className="mt-1 text-sm text-slate-600">Pick a person to start a direct message.</div>

                    <div className="mt-4">
                        <input
                            type="text"
                            value={dmSearch}
                            onChange={(e) => setDmSearch(e.target.value)}
                            placeholder="Search by name or role"
                            className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>

                    <div className="mt-4 max-h-72 overflow-auto rounded-xl border border-slate-200 bg-white">
                        {dmCandidates.length === 0 ? (
                            <div className="px-4 py-3 text-sm text-slate-500">No users found.</div>
                        ) : (
                            dmCandidates.map((u) => (
                                <button
                                    key={u.id}
                                    type="button"
                                    disabled={startingDm}
                                    onClick={() => startDm(u.id)}
                                    className="w-full px-4 py-3 text-left hover:bg-amber-50 focus:bg-amber-50 focus:outline-none disabled:opacity-60"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="truncate text-sm font-semibold text-slate-900">{u.name}</div>
                                            <div className="text-xs text-slate-500">{String(u.role || '').toUpperCase()}</div>
                                        </div>
                                        <span className="text-xs font-semibold text-amber-700">Message</span>
                                    </div>
                                </button>
                            ))
                        )}
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setShowDmModal(false)}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
