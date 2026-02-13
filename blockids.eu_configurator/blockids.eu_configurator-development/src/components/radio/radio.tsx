'use client';

import clsx from 'clsx';
import { BaseSyntheticEvent, forwardRef, LegacyRef, memo, ReactNode, useCallback } from 'react';
import classes from './radio.module.scss';

export interface IRadioProps {
    id: string
    label?: string | ReactNode
    value?: string
    checked?: boolean
    error?: string | undefined
    onChange?: (arg: BaseSyntheticEvent) => void
    className?: string
    isDisabled: boolean
}

const Radio = forwardRef((
    {
        id,
        label,
        value,
        checked,
        error,
        onChange,
        className,
        isDisabled,
        ...otherProps
    }: IRadioProps, ref: LegacyRef<HTMLInputElement>,
) => {
    const handleChange = useCallback((e: BaseSyntheticEvent) => {
        onChange?.(e);
    }, [onChange]);

    return (
        <div className={clsx(classes.root, isDisabled && classes.disabled, className)}>
            <label
                htmlFor={id}
            >
                <input
                    id={id}
                    type="radio"
                    value={value}
                    checked={checked}
                    onChange={handleChange}
                    disabled={isDisabled}
                />
                {label}
                <span className={classes.marker} />
            </label>
        </div>
    );
});

export default memo(Radio);
