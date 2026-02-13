import clsx from 'clsx';
import NextLink from 'next/link';
import { memo, ReactNode } from 'react';

import classes from './link.module.scss';

export interface ILinkProps {
    className?: string
    href: string
    children: ReactNode
}

const Link = (
    {
        className,
        href = '/',
        children,
    }: ILinkProps,
) => {
    return (
        <NextLink
            className={clsx(classes.root, className)}
            href={href}
        >
            {children}
        </NextLink>
    );
};

export default memo(Link);
