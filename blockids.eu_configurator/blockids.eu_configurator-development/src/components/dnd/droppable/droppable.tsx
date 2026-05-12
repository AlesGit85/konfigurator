'use client';

import { useDroppable } from '@dnd-kit/core';
import clsx from 'clsx';
import { memo, ReactNode } from 'react';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import * as CONST from '@/lib/constants';
import classes from './droppable.module.scss';

type DroppableTypes = typeof CONST.DROPPABLE_TYPE_DEFAULT | typeof CONST.DROPPABLE_TYPE_DISABLED | typeof CONST.DROPPABLE_TYPE_DIRECTION

export interface IDroppableProps {
    id: number | string
    className?: string
    children: ReactNode
    direction?: GridDirectionType
    type?: DroppableTypes
}

const Droppable = (
    {
        id,
        className,
        children,
        type = CONST.DROPPABLE_TYPE_DEFAULT,
        direction = CONST.GRID_ALIGNMENT_VERTICAL,
    }: IDroppableProps,
) => {
    const { isOver, setNodeRef, node } = useDroppable({
        id,
        disabled: type === CONST.DROPPABLE_TYPE_DISABLED,
    });

    const style = {
        color: isOver ? 'green' : undefined,
    };

    return (
        <div
            className={clsx(classes.root, direction && classes[direction], type && classes[type], className)}
            ref={setNodeRef}
            style={style}
            data-position={id}
        >
            {children}
        </div>
    );
};

export default memo(Droppable);
