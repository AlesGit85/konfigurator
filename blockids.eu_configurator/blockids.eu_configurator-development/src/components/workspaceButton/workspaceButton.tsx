import clsx from 'clsx';
import { memo, useCallback } from 'react';
import Button from '@/components/button/button';
import DummySvg from '/public/icons/workspace-desk.svg';
import classes from './workspaceButton.module.scss';

interface IWorkspaceButtonProps {
    className?: string
    id?: string
    image?: string
    title?: string
    description?: string
    action?: {
        title: string,
        onClick: (arg: string) => void,
    }
}

const WorkspaceButton = (
    {
        className,
        id,
        image,
        title,
        description,
        action,
    }: IWorkspaceButtonProps,
) => {
    const handleClick = useCallback(() => {
        action?.onClick?.(id);
    }, []);

    return (
        <div
            className={clsx(classes.root, className)}
            id={id}
        >
            <div className={clsx(classes.image, id === 'outdoor' && classes.greyish)}>
                <DummySvg className={classes.img}/>
            </div>
            <div className={classes.content}>
                <div className={classes.title}>{title}</div>
                <div className={classes.description}>{description}</div>
            </div>
            <div className={classes.control}>
                <Button
                    className={classes.button}
                    onClick={handleClick}
                >
                    {action?.title}
                </Button>
            </div>
        </div>
    );
};

export default memo(WorkspaceButton);
