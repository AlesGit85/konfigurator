import { memo } from 'react';
import { useTranslation } from 'react-i18next';
import Button from '@/components/button/button';
import classes from './control.module.scss';

export interface IControlProps {
    onProceed: () => void
    onClose: () => void
}

const Control = (
    {
        onProceed,
        onClose,
    }: IControlProps,
) => {
    const { t } = useTranslation();

    return (
        <div className={classes.root}>
            <Button onClick={onProceed}>{t('help:proceed')}</Button>
            <Button onClick={onClose}>{t('help:cancel')}</Button>
        </div>
    );
};

export default memo(Control);
