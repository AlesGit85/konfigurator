'use client';

import clsx from 'clsx';
import { BaseSyntheticEvent, forwardRef, LegacyRef, memo } from 'react';
import classes from './input.module.scss';

export interface IInputProps {
    id: string
    className?: string
    labelClassName?: string
    inputClassName?: string
    label?: string
    type: 'password' | 'text' | 'email' | 'hidden'
    placeholder?: string
    value?: string | number
    disabled?: boolean
    error?: boolean
    onChange?: (event: BaseSyntheticEvent) => void
    onBlur?: (event: BaseSyntheticEvent) => void
    onKeyUp?: (event: BaseSyntheticEvent) => void
}

const Input = forwardRef((
    {
        id,
        className,
        labelClassName,
        inputClassName,
        label,
        type,
        placeholder,
        disabled,
        error,
        value,
        ...otherProps
    }: IInputProps,
    ref: LegacyRef<HTMLInputElement>,
) => {
    return (
        <div className={clsx(classes.root, disabled && classes.disabled, className)}>
            {label &&
                <label
                    className={clsx(classes.label, labelClassName)}
                    htmlFor={id}
                >
                    {label}
                </label>
            }
            <input
                {...otherProps}
                ref={ref}
                name={id}
                className={clsx(classes.input, error && classes.error, inputClassName)}
                placeholder={placeholder}
                type={type}
                value={value}
                readOnly={disabled}
            />
        </div>
    );
});

export default memo(Input);
