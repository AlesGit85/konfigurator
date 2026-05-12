import clsx from 'clsx';
import { memo, ReactNode } from 'react';
import classes from './main.module.scss';

export interface IMainProps {
    className?: string
    children: ReactNode
}

const Main = (
    {
        className,
        children,
    }: IMainProps,
) => {
    return (
        <main className={clsx(classes.root, className)}>
            {children}
        </main>
    );
};

export default memo(Main);
