'use client';

import LightBulbSvg from '/public/icons/lightbulb.svg';
import classes from './startHere.module.scss';
import clsx from 'clsx';
import { useTranslation } from 'react-i18next';
import { createPortal } from 'react-dom';
import { memo, useCallback, useState } from 'react';
import Help from '@/components/help/help';

export interface IStartHereProps {
    className?: string
}

const StartHere = (
    {
        className,
    }: IStartHereProps,
) => {
    const { t } = useTranslation();

    const [open, setOpen] = useState<boolean>(false);

    const handleHelp = useCallback(() => {
        setOpen(prevState => !prevState);
    }, []);

    return (
        <>
            <div
                className={clsx(classes.root, className)}
            >
                <LightBulbSvg
                    className={classes.icon}
                    onClick={handleHelp}
                />
                <span className={classes.text}>
                    {t('grid:startHere')}
                </span>
            </div>
            {open && createPortal(
                <Help
                    title={t('help:title')}
                    description={t('help:startHereText')}
                    onClose={handleHelp}
                />,
                document?.getElementById('grid-template') as Element,
            )}
        </>
    );
};

export default memo(StartHere);
