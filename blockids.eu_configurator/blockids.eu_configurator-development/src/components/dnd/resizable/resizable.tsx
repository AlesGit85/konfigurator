import clsx from 'clsx';
import { NumberSize, Resizable as ReResizable, ResizeDirection } from 're-resizable';
import React, { memo, useCallback, useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import { useResizable } from '@/hooks/useResizable';
import { GRID_CONFIG } from '@/lib/grid';
import { convert } from '@/lib/utils';
import {
    configuratorIndividualSizeSelector, configuratorRealtimeIndividualSizeSelector, setHistory,
    setIndividualAxis, setRealtimeIndividualAxis, updateMattressPrice,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import { AxisType } from '@/redux/types/configuratorTypes';
import classes from './resizable.module.scss';

export interface IResizableProps {
    direction: GridDirectionType
    initialData: {
        size: {
            individual: AxisType,
        },
        direction: GridDirectionType,
    }
    readOnly: boolean
}

const Resizable = (
    {
        direction,
        initialData,
        readOnly,
    }: IResizableProps,
): JSX.Element => {
    const dispatch: AppDispatch = useDispatch();

    const configuratorIndividualSize = useSelector(configuratorIndividualSizeSelector);
    const configuratorRealtimeIndividualSize = useSelector(configuratorRealtimeIndividualSizeSelector);

    const { minAllowedWidth, minAllowedHeight } = useResizable({ direction });

    const gridConfigDirection = GRID_CONFIG[direction];

    const defaultWidth: number = gridConfigDirection.defaultWidth;
    const defaultHeight: number = gridConfigDirection.defaultHeight;
    const cmPixelX: number = gridConfigDirection.cmToPixelX;
    const cmPixelY: number = gridConfigDirection.cmToPixelY;
    const pixelToCmY: number = gridConfigDirection.pixelToCmY;
    const pixelToCmX: number = gridConfigDirection.pixelToCmX;
    const cmMaxX: number = gridConfigDirection.cmMaxX;
    const cmMaxY: number = gridConfigDirection.cmMaxY;

    const configuratorIndividualSizeAxisX = configuratorIndividualSize.axisX;
    const configuratorIndividualSizeAxisY = configuratorIndividualSize.axisY;
    const configuratorRealtimeIndividualSizeAxisX = configuratorRealtimeIndividualSize.axisX;
    const configuratorRealtimeIndividualSizeAxisY = configuratorRealtimeIndividualSize.axisY;

    const initialSizesIndividual: AxisType = initialData.size.individual;

    const [realtimeWidth, setRealtimeWidth] = useState<number>(defaultWidth);
    const [width, setWidth] = useState<number>(defaultWidth);
    const [realtimeHeight, setRealtimeHeight] = useState<number>(defaultHeight);
    const [height, setHeight] = useState<number>(defaultHeight);

    useEffect(() => {
        const isInitialData = initialData.direction === direction;
        const axisX: number = isInitialData ? (initialSizesIndividual.axisX || cmMaxX) : cmMaxX;
        const axisY: number = isInitialData ? (initialSizesIndividual.axisY || cmMaxY) : cmMaxY;

        dispatch(setIndividualAxis( {
            axisX,
            axisY,
        } ));
        dispatch(setRealtimeIndividualAxis( {
            axisX,
            axisY,
        } ));
    }, [dispatch, gridConfigDirection]);

    useEffect(() => {
        setWidth(convert(configuratorIndividualSizeAxisX, pixelToCmX));
        setHeight(convert(configuratorIndividualSizeAxisY, pixelToCmY));
        setRealtimeWidth(convert(configuratorIndividualSizeAxisX, pixelToCmX));
        setRealtimeHeight(convert(configuratorIndividualSizeAxisY, pixelToCmY));
    }, [configuratorIndividualSizeAxisX, configuratorIndividualSizeAxisY]);

    useEffect(() => {
        setRealtimeWidth(convert(configuratorRealtimeIndividualSizeAxisX, pixelToCmX));
        setRealtimeHeight(convert(configuratorRealtimeIndividualSizeAxisY, pixelToCmY));
    }, [configuratorRealtimeIndividualSizeAxisX, configuratorRealtimeIndividualSizeAxisY]);

    const handleResizeStop = useCallback((e: MouseEvent | TouchEvent, _: ResizeDirection, ref: HTMLElement, d: NumberSize) => {
        setWidth(prevState => prevState + d.width);
        setHeight(prevState => prevState + d.height);
        dispatch(setIndividualAxis( {
            ...({ axisX: configuratorIndividualSizeAxisX + Math.round(cmPixelX * d.width) }),
            ...({ axisY: configuratorIndividualSizeAxisY + Math.round(cmPixelY * d.height) }),
        } ));
        setTimeout(() => { dispatch(setHistory()); });
    }, [dispatch, configuratorIndividualSizeAxisX, configuratorIndividualSizeAxisY, cmPixelX, cmPixelY]);

    const handleResize = useCallback((e: MouseEvent | TouchEvent, _: ResizeDirection, ref: HTMLElement, d: NumberSize) => {
        dispatch(setRealtimeIndividualAxis( {
            ...({ axisX: configuratorIndividualSizeAxisX + Math.round(cmPixelX * d.width) }),
            ...({ axisY: configuratorIndividualSizeAxisY + Math.round(cmPixelY * d.height) }),
        } ));
        dispatch(updateMattressPrice());
    }, [dispatch, configuratorIndividualSizeAxisX, configuratorIndividualSizeAxisY, cmPixelX, cmPixelY]);

    const recWidth: number = useMemo(() => defaultWidth + 35, [defaultWidth]);
    const recHeight: string = useMemo(() => 'calc(100% + 30px)', []);

    return (
        <div className={classes.root}>
            <div className={classes.space}>
                <div
                    className={clsx(classes.disabled, classes.top)}
                    style={{
                        height: realtimeHeight < minAllowedHeight ? defaultHeight - minAllowedHeight : defaultHeight - realtimeHeight,
                        // height: -heightC,
                    }}
                />
            </div>
            <div className={classes.resizeable}>
                <ReResizable
                    size={{ width, height }}
                    onResizeStop={handleResizeStop}
                    onResize={handleResize}
                    maxWidth={defaultWidth}
                    maxHeight={defaultHeight}
                    minWidth={minAllowedWidth}
                    minHeight={minAllowedHeight}
                    className={clsx(classes.container, readOnly && classes.readonly)}
                    handleClasses={{
                        top: clsx(classes.link, classes.top, readOnly && classes.readonly),
                        right: clsx(classes.link, classes.right, readOnly && classes.readonly),
                        bottom: clsx(classes.link, classes.hidden),
                        left: clsx(classes.link, classes.hidden),
                        bottomLeft: clsx(classes.link, classes.hidden),
                        bottomRight: clsx(classes.link, classes.hidden),
                    }}
                    handleStyles={{
                        top: {
                            width: `${recWidth}px`,
                        },
                        right: {
                            height: recHeight,
                        },
                    }}
                />
                <div
                    className={clsx(classes.disabled, classes.aside)}
                    style={{
                        width: realtimeWidth < minAllowedWidth ? defaultWidth - minAllowedWidth : defaultWidth - realtimeWidth,
                        // width: -widthC,
                    }}
                >
                    <div className={classes.after} />
                </div>
            </div>
        </div>

    );
};

export default memo(Resizable);
