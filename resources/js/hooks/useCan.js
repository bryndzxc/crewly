import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

export default function useCan() {
    const { props } = usePage();
    const canMap = props?.can || {};

    return useCallback(
        (abilityKey) => {
            if (!abilityKey) return true;
            return Boolean(canMap[abilityKey]);
        },
        [canMap]
    );
}
