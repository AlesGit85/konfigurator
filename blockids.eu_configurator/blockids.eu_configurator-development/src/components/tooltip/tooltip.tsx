import clsx from 'clsx';
import { Children, cloneElement, memo, ReactNode } from 'react';

import classes from './tooltip.module.scss';

export interface ITooltipProps {
    className?: string
    children: ReactNode
    text: string
}

const Tooltip = (
    {
        className,
        children,
        text,
    }: ITooltipProps,
) => {
    return (
        <>
            {Children.map(children, (child) => cloneElement(child, { className: clsx(classes.root, child?.props?.className, className), 'data-tooltip': text }))}
        </>
    );
};

export default memo(Tooltip);
