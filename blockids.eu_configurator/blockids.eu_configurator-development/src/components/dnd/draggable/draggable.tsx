'use client';

import { useDraggable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import clsx from 'clsx';
import Image from 'next/image';
import React, { memo, ReactNode } from 'react';
import Control from '@/components/dnd/draggable/_components/control/control';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import * as CONST from '@/lib/constants';
import classes from './draggable.module.scss';

export type DraggableDataType = {
    name: string,
    image: string,
    type: string,
    price: {
        value: number,
        currency: string,
    },
    rotation?: number,
    overlay?: object[],
}

export interface IDraggableProps {
    className?: string
    draggableId: string | number
    id: string | number
    children?: ReactNode
    direction?: GridDirectionType
    dropped?: boolean
    cellId?: string
    data?: DraggableDataType
    isDisabled?: boolean
}

const Draggable = (
    {
        className,
        draggableId,
        id,
        children,
        direction = CONST.GRID_ALIGNMENT_VERTICAL,
        dropped,
        cellId,
        data,
        isDisabled,
        ...otherProps
    }: IDraggableProps,
) => {
    const { attributes, listeners, setNodeRef, transform } = useDraggable({
        id: draggableId,
        data: {
            id,
            cellId,
            ...data,
        },
    });

    const rotation: boolean = !!data?.rotation; // 180 | 0
    const holdOverlay = data?.overlay;

    const style = transform ? {
        transform: CSS.Translate.toString(transform),
        cursor: 'grabbing',
        zIndex: 9999,
    } : undefined;

    const dndProps = {
        ...listeners,
        ...attributes,
    };

    return (
        <div
            className={clsx(classes.root, direction && classes[direction], dropped && classes.dropped, isDisabled && classes.disabled, className)}
            ref={setNodeRef}
            style={style}
            data-id={id}
            {...(!dropped && dndProps)}
            {...otherProps}
            tabIndex={0}
            role={'button'}
        >
            {children}
            {data?.image &&
                <div className={clsx(classes.desk, rotation && classes.inverted)}>
                    <Image
                        className={clsx(classes.image, classes[data.type])}
                        src={data?.image}
                        alt={data?.name || ''}
                        width={100}
                        height={100}
                        priority={true}
                    />
                    {holdOverlay?.image &&
                        <Image
                            className={classes.overlay}
                            src={holdOverlay?.image}
                            alt={'grips'}
                            width={100}
                            height={100}
                            priority={true}
                        />
                    }
                    {isDisabled && data?.name && <div className={classes.name}>{data.name}</div>}
                </div>
            }
            {dropped &&
                <Control
                    className={classes.control}
                    identifier={cellId}
                    data={{
                        deskType: data?.type,
                    }}
                    {...(dropped && dndProps)}
                />
            }
        </div>
    );
};

export default memo(Draggable);
