import { memo } from 'react';
import { useTranslation } from 'react-i18next';
import Button from '@/components/button/button';
import classes from './customControl.module.scss';

export interface ICustomControlProps {
    buttons: {
        onAction: () => void,
        title: string,
    }[]
}

const CustomControl = (
    {
        buttons,
    }: ICustomControlProps,
) => {
    const { t } = useTranslation();

    return (
        <div className={classes.root}>
            {buttons.map(button => {
                return (
                    <Button
                        key={button.title}
                        onClick={button.onAction}
                    >
                        {button.title}
                    </Button>
                );
            })}
        </div>
    );
};

export default memo(CustomControl);
