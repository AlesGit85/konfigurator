import clsx from 'clsx';
import { memo } from 'react';
import { v4 as uuidv4 } from 'uuid';
import Draggable from '@/components/dnd/draggable/draggable';
import { GridDirectionType } from '@/components/dnd/grid/grid';

import classes from './boards.module.scss';

export interface IBoardsProps {
    className?: string
    direction: GridDirectionType
    data: {
        id: number,
        image: string,
        title: string,
        type: string,
        price: number,
        currency: string,
    }[]
    readOnly: boolean
}

const Boards = (
    {
        className,
        direction,
        data,
        readOnly,
    }: IBoardsProps,
) => {
    return (
        <div className={clsx(classes.root, className)}>
            {data.map((item) => {
                return (
                    <div
                        className={classes.item}
                        key={item.id}
                    >
                        <Draggable
                            draggableId={uuidv4()}
                            id={item.id}
                            direction={direction}
                            data={{
                                name: item.title,
                                image: item.image,
                                type: item.type,
                                price: {
                                    value: item.price,
                                    currency: item.currency,
                                },
                            }}
                            isDisabled={readOnly}
                        />
                        <span className={classes.tooltip}>{item.title}</span>
                    </div>
                );
            })}

        </div>
    );
};

export default memo(Boards);
