'use client';

import { Fancybox as NativeFancybox } from '@fancyapps/ui';

import { OptionsType } from '@fancyapps/ui/types/Fancybox/options';
import React, { useRef, useEffect, PropsWithChildren } from 'react';

import '@fancyapps/ui/dist/fancybox/fancybox.css';

interface Props {
    options?: Partial<OptionsType>
    delegate?: string
}

function Fancybox(props: PropsWithChildren<Props>) {
    const containerRef = useRef(null);

    useEffect(() => {
        const container = containerRef.current;

        const delegate = props.delegate || '[data-fancybox]';
        const options = props.options || {};

        NativeFancybox.bind(container, delegate, options);

        return () => {
            NativeFancybox.unbind(container);
            NativeFancybox.close();
        };
    }, []);

    return <div ref={containerRef}>{props.children}</div>;
}

export default Fancybox;
