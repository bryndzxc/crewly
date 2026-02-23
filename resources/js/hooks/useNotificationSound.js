import { useCallback, useEffect, useRef } from 'react';

import notificationSoundUrl from '../../notification.mp3';

export default function useNotificationSound(enabled = true) {
    const enabledRef = useRef(Boolean(enabled));
    const unlockedRef = useRef(false);
    const audioRef = useRef(null);
    const lastPlayedAtRef = useRef(0);

    useEffect(() => {
        enabledRef.current = Boolean(enabled);
    }, [enabled]);

    const unlock = useCallback(() => {
        if (unlockedRef.current) return;
        unlockedRef.current = true;

        try {
            const a = new Audio(notificationSoundUrl);
            a.preload = 'auto';
            audioRef.current = a;
        } catch {
            // ignore
        }
    }, []);

    useEffect(() => {
        const onFirstInteraction = () => unlock();
        window.addEventListener('pointerdown', onFirstInteraction, { once: true });
        window.addEventListener('keydown', onFirstInteraction, { once: true });
        return () => {
            window.removeEventListener('pointerdown', onFirstInteraction);
            window.removeEventListener('keydown', onFirstInteraction);
        };
    }, [unlock]);

    const play = useCallback(() => {
        if (!enabledRef.current) return;

        const now = Date.now();
        if (now - lastPlayedAtRef.current < 1000) return;
        lastPlayedAtRef.current = now;

        const a = audioRef.current;
        if (!a) return;

        try {
            a.currentTime = 0;
            const p = a.play();
            if (p && typeof p.catch === 'function') {
                p.catch(() => {});
            }
        } catch {
            // ignore
        }
    }, []);

    return { play, unlock, src: notificationSoundUrl };
}
