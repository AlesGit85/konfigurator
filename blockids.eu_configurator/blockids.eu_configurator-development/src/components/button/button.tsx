import clsx from 'clsx';
import { BaseSyntheticEvent, memo, ReactNode, useCallback } from 'react';
import ToastContainerWrapper from '@/components/notify/toastContainerWrapper';
import Spinner from '@/components/spinner/spinner';
import * as CONST from '@/lib/constants';
import classes from './button.module.scss';

export interface IButtonProps {
    className?: string
    id?: string
    type?: 'submit' | 'reset' | 'button' | undefined
    theme?: typeof CONST.THEME_PRIMARY | typeof CONST.THEME_SECONDARY | typeof CONST.THEME_TERTIARY | typeof CONST.THEME_TRANSPARENT
    onClick?: (event: BaseSyntheticEvent) => void
    children: ReactNode
    isLoading?: boolean
    isDisabled?: boolean
}

const Button = (
    {
        className,
        id,
        type = 'button',
        theme = CONST.THEME_PRIMARY,
        onClick,
        children,
        isLoading,
        isDisabled,
        ...otherProps
    }: IButtonProps,
) => {
    const handleClick = useCallback((e: BaseSyntheticEvent) => !isDisabled && onClick?.(e), [isDisabled, onClick]);

    return (
        <div
            className={clsx(classes.root, theme && classes[theme], isLoading && classes.loading, isDisabled && classes.disabled, className)}
            onClick={handleClick}
            role={'button'}
            tabIndex="0"
            {...otherProps}
        >
            {children}
            {isLoading && <Spinner className={classes.spinner} />}
            <ToastContainerWrapper
                containerId={id}
                className={classes.toast}
            />
        </div>
    );
};

export default memo(Button);
