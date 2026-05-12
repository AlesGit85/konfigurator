'use client';

import clsx from 'clsx';
import FilePlusSvg from '/public/icons/file-plus.svg';
import { memo } from 'react';
import { useTranslation } from 'react-i18next';
import Button from '@/components/button/button';
import headerClasses from '@/components/header/header.module.scss';
import { useDraft } from '@/hooks/useDraft';
import * as CONST from '@/lib/constants';
import classes from './notFound.module.scss';
import LogoSvg from '/public/logo.svg';

export interface INotFoundProps {

}

const NotFound = (
    {

    }: INotFoundProps,
) => {
    const { t } = useTranslation();

    const { createDraft } = useDraft();

    return (
        <div className={classes.root}>
            <div className={classes.container}>
                <div className={classes.brand}>
                    <LogoSvg/>
                    <h1 className={clsx(headerClasses.logo, classes.column)}>
                        <span className={headerClasses.title}>
                            {t('header:appTitle')}
                        </span>
                        <span>
                            {t('header:404')}
                        </span>
                    </h1>
                    <div className={clsx(headerClasses.buttons, classes.action)}>
                        <Button
                            className={clsx(headerClasses.button, headerClasses.icon)}
                            theme={CONST.THEME_PRIMARY}
                            onClick={createDraft.onCreateDraftClick}
                            isLoading={createDraft.createLoading}
                        >
                            <FilePlusSvg/>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default memo(NotFound);
