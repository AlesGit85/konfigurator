'use client';

import clsx from 'clsx';
import { BaseSyntheticEvent, forwardRef, LegacyRef, memo, ReactNode, useCallback } from 'react';
import classes from './checkbox.module.scss';

export interface IInput {
    id: string
    label?: string
    error?: string | undefined
    onChange?: (event: BaseSyntheticEvent) => void
    checked?: boolean
    className?: string
    markerClassNames?: string
    isDisabled?: boolean
    children: ReactNode
}

const Checkbox = forwardRef((
    {
        children,
        id,
        label,
        error,
        onChange,
        checked = false,
        className,
        markerClassNames,
        isDisabled,
        ...otherProps
    }: IInput, ref: LegacyRef<HTMLInputElement>,
) => {
    const handleChange = useCallback((event: BaseSyntheticEvent) => {
        onChange?.(event);
    }, [onChange]);

    return (
        <div className={clsx(classes.root, isDisabled && classes.disabled, className)}>
            <label htmlFor={id}>
                <input
                    {...otherProps}
                    ref={ref}
                    type="checkbox"
                    id={id}
                    onChange={handleChange}
                    checked={checked}
                    disabled={isDisabled}
                />
                <div className={clsx(classes.marker, classes.markerClassNames, error && classes.markerError)}/>
                {children}
            </label>
        </div>
    );
});

export default memo(Checkbox);
