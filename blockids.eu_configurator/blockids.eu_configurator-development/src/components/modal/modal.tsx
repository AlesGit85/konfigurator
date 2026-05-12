import clsx from 'clsx';
import { memo, useCallback, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import WorkspaceButton from '@/components/workspaceButton/workspaceButton';
import * as CONST from '@/lib/constants';
import classes from './modal.module.scss';

interface IModalProps {
    className?: string
    onClick: (arg: string) => void
}

const Modal = (
    {
        className,
        onClick,
    }: IModalProps,
) => {
    const { t } = useTranslation();

    useEffect(() => {
        document.body.classList.add('blur');
        return () => {
            document.body.classList.remove('blur');
        };
    }, []);

    const handleClick = useCallback((id: string) => {
        onClick?.(id);
    }, [onClick]);

    return (
        <div className={clsx(classes.root, className)}>
            <div className={classes.container}>
                <div className={classes.content}>
                    <div className={classes.header}>
                        {t('modal:title')}
                    </div>
                    <div className={classes.body}>
                        <WorkspaceButton
                            id={CONST.WORKSPACE_TYPE_INDOOR}
                            className={classes.button}
                            title={t('modal:indoorTitle')}
                            description={t('modal:indoorDescription')}
                            action={{
                                title: t('modal:indoorActionTitle'),
                                onClick: handleClick,
                            }}
                        />
                        <WorkspaceButton
                            id={CONST.WORKSPACE_TYPE_OUTDOOR}
                            className={classes.button}
                            title={t('modal:outdoorTitle')}
                            description={t('modal:outdoorDescription')}
                            action={{
                                title: t('modal:outdoorActionTitle'),
                                onClick: handleClick,
                            }}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default memo(Modal);
