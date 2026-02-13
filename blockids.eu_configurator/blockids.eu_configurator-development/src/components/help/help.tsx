import clsx from 'clsx';
import CloseIconSvg from '/public/icons/circle.svg';
import { memo } from 'react';
import classes from './help.module.scss';

export interface IHelpProps {
    className?: string
    title: string
    description: string
    onClose?: () => void
    customControlComponent?: JSX.Element
}

const Help = (
    {
        className,
        title,
        description,
        onClose,
        customControlComponent,
    }: IHelpProps,
) => {
    return (
        <div className={clsx(classes.root, className)}>
            <div className={classes.container}>
                <div className={classes.content}>
                    <div className={classes.header}>
                        <div className={classes.title}>{title}</div>
                        <button
                            className={classes.close}
                            onClick={onClose}
                        >
                            <CloseIconSvg/>
                        </button>
                    </div>
                    <div className={classes.body}>
                        <p className={classes.description}>{description}</p>
                        {customControlComponent}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default memo(Help);
