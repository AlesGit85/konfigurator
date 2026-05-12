'use client';

import BanSvg from '/public/icons/ban.svg';
import classes from './notAllowed.module.scss';
import clsx from 'clsx';
import { useTranslation } from 'react-i18next';
import { memo } from 'react';

export interface INotAllowedProps {
    className?: string
}

const NotAllowed = (
    {
        className,
    }: INotAllowedProps,
) => {
    const { t } = useTranslation();

    return (
        <div className={clsx(classes.root, className)}>
            <BanSvg className={classes.icon} />
            <span className={classes.text}>
                {t('grid:notAllowed')}
            </span>
        </div>
    );
};

export default memo(NotAllowed);
