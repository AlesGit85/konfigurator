import clsx from 'clsx';
import { memo } from 'react';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import classes from './orientation.module.scss';

export interface IOrientationProps {
    className?: string
    direction: GridDirectionType
}

const Orientation = (
    {
        className,
        direction,
    }: IOrientationProps,
) => {
    return (
        <div className={clsx(classes.root, direction && classes[direction], className)} />
    );
};

export default memo(Orientation);
