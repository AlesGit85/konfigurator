import clsx from 'clsx';
import SpinnerSvg from '/public/icons/spinner.svg';
import { memo, ReactNode } from 'react';
import classes from './spinner.module.scss';

export interface ISpinnerProps {
    className?: string
    titleClassName?: string
    title?: string
    children?: ReactNode
}

const Spinner = (
    {
        className,
        titleClassName,
        title,
        children,
    }: ISpinnerProps,
) => {
    return (
        <span className={clsx(classes.root, className)}>
            <div className={clsx(classes.spin, titleClassName)}>
                <SpinnerSvg/>
                {title}
            </div>
            {children}
        </span>
    );
};

export default memo(Spinner);
