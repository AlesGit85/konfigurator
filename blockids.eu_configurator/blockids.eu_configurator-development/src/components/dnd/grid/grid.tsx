'use client';

import { useDndMonitor } from '@dnd-kit/core';
import clsx from 'clsx';
import { dir } from 'i18next';
import { memo, useCallback, useEffect, useMemo, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { v4 as uuidv4 } from 'uuid';
import AxisX from '@/components/dnd/axisX/axisX';
import AxisY from '@/components/dnd/axisY/axisY';
import Draggable from '@/components/dnd/draggable/draggable';
import Droppable from '@/components/dnd/droppable/droppable';
import NotAllowed from '@/components/dnd/grid/_components/notAllowed/notAllowed';
import StartHere from '@/components/dnd/grid/_components/startHere/startHere';
import Mattress from '@/components/dnd/mattress/mattress';
import Resizable from '@/components/dnd/resizable/resizable';
import * as CONST from '@/lib/constants';
import { DATA } from '@/lib/data';
import { GRID_CONFIG } from '@/lib/grid';
import { getDeskType, getHoldCount, getMattressCount, getRandomNumber } from '@/lib/utils';
import {
    configuratorCustomerTypeSelector,
    configuratorGridTemplateSelector,
    configuratorHoldsSelector,
    configuratorSettingsSelector,
    setHistory,
    setGrid,
    setStandardAxis,
    updateAccessoryCount,
    updateOverlay, updateMattressPrice, configuratorGridCountMaxSelector, recalculateDraft,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import { AxisType } from '@/redux/types/configuratorTypes';
import classes from './grid.module.scss';

export type GridDirectionType = typeof CONST.GRID_ALIGNMENT_VERTICAL | typeof CONST.GRID_ALIGNMENT_HORIZONTAL;

export interface IGridProps {
    className?: string
    direction?: GridDirectionType
    initialData: {
        size: {
            individual: AxisType,
        },
        direction: GridDirectionType,
    }
    readOnly: boolean
}

const Grid = (
    {
        className,
        direction = CONST.GRID_ALIGNMENT_VERTICAL,
        initialData,
        readOnly,
    }: IGridProps,
) => {
    const dispatch: AppDispatch = useDispatch();

    const mountedRef = useRef<boolean>(false);

    const configuratorGridTemplate = useSelector(configuratorGridTemplateSelector);
    const customerType = useSelector(configuratorCustomerTypeSelector);
    const configuratorHoldSelected = useSelector(configuratorHoldsSelector);
    const configuratorSettings = useSelector(configuratorSettingsSelector);
    const configuratorGridCountMax = useSelector(configuratorGridCountMaxSelector);

    const gridConfigDirection = GRID_CONFIG[direction];
    const baseRowX = gridConfigDirection.baseRowX;

    // Monitor drag and drop events that happen on the parent `DndContext` provider
    useDndMonitor({
        onDragEnd(event) {
            const { over, active } = event;

            // If the item is dropped over a container, set it as the parent
            // otherwise reset the parent to `null`

            dispatch(setGrid({
                source: {
                    cellId: active ? active?.data?.current?.cellId : '',
                    value: '',
                },
                target: {
                    cellId: over ? over?.id : '',
                    value: active ? active?.data?.current?.id : '',
                    image: active ? active?.data?.current?.image : '',
                    name: active ? active?.data?.current?.name : '',
                    type: active ? active?.data?.current?.type : '',
                    price: active ? active?.data?.current?.price?.value : '',
                    currency: active ? active?.data?.current?.price?.currency : '',
                    overlay: active ? active?.data?.current?.overlay : '',
                    rotation: active ? active?.data?.current?.rotation : 0,
                },
            }));
            dispatch(setStandardAxis());
            dispatch(recalculateDraft());
            !!configuratorHoldSelected?.overlays && dispatch(updateOverlay( { isRemove: false, isOverlayChange: false } ));
            dispatch(updateMattressPrice());
            over?.id && setTimeout(() => { dispatch(setHistory()); });
            // dispatch(setHistory());
        },
        onDragCancel(event) {},
    });

    /**
     * Update draftControl - holds price
     */
    useEffect(() => {
        const count: number = getHoldCount(configuratorGridTemplate);
        !!configuratorHoldSelected?.id && dispatch(updateAccessoryCount( { type: CONST.ACCESSORY_TYPE_HOLDS, count, price: configuratorHoldSelected.price } ));
    }, [dispatch, configuratorGridTemplate, configuratorHoldSelected]);

    useEffect(() => {
        dispatch(setStandardAxis());
    }, []);

    useEffect(() => {
        dispatch(updateOverlay( { isRemove: !configuratorHoldSelected?.overlays, isOverlayChange: true } ));
        // if (mountedRef.current) {
        //     dispatch(setHistory());
        // } else {
        //     mountedRef.current = true;
        // }
    }, [dispatch, configuratorHoldSelected]);

    const containers = DATA[direction];

    const mattressCount = useMemo(() => getMattressCount({
        direction,
        deskHorizontalCount: configuratorGridCountMax,
        isPersonal: customerType === CONST.CUSTOMER_TYPE_FAMILY,
        isIndividualSize: configuratorSettings.individual.axisX === configuratorSettings.standard.axisX + gridConfigDirection.cmMinCutX,
    }), [direction, configuratorSettings.individual.axisX, configuratorSettings.standard.axisX, customerType, configuratorGridCountMax]);

    const isDropAllowed = useCallback((cellId: string) => {
        const xId: string = cellId.substring(0, 1);
        const yId: number = Number(cellId.substring(1));
        const gridConfigDirectionAxisX: string[] = gridConfigDirection.axisX;
        const i: number = gridConfigDirectionAxisX.findIndex(x => x.includes(xId)) + 1;
        const previousLetterToCheck: string = gridConfigDirectionAxisX[i - 2];
        const nextLetterToCheck: string = gridConfigDirectionAxisX[i];

        const cellAbove = configuratorGridTemplate[xId + (yId + 1)];
        const cellBelow = configuratorGridTemplate[xId + (yId - 1)];
        const previousLetter = configuratorGridTemplate[previousLetterToCheck + (yId)];
        const nextLetter = configuratorGridTemplate[nextLetterToCheck + (yId)];

        return cellBelow || nextLetter || cellAbove || previousLetter;
    }, [configuratorGridTemplate, gridConfigDirection.axisX]);

    return (
        <div
            className={clsx(classes.root, direction && classes[direction], className)}
            id={'grid-template'}
        >
            <Resizable
                direction={direction}
                initialData={{
                    size: initialData.size,
                    direction: initialData.direction,
                }}
                readOnly={readOnly}
            />
            <AxisX
                className={classes.axisX}
                source={gridConfigDirection.axisX}
                base={gridConfigDirection.cmBaseX}
            />
            <AxisY
                className={classes.axisY}
                source={gridConfigDirection.axisY}
                base={gridConfigDirection.cmBaseY}
            />
            <div className={classes.container}>
                {containers.map((item, index) => {
                    const title: string | JSX.Element = !!index ? '' : <StartHere />;
                    const disabled: JSX.Element = <NotAllowed />;
                    const isCustomerTypePublic: boolean = customerType === CONST.CUSTOMER_TYPE_PUBLIC;
                    const gridMaxAllowed: number = gridConfigDirection.maxAllowed[customerType];
                    const isDisabled = index + 1 > gridMaxAllowed || (customerType === CONST.CUSTOMER_TYPE_PUBLIC ? index > 1 : index) && !isDropAllowed(item.id); // zobrazuje vsechny pole jako disabled, at uzivatel vi kam muze vkladat
                    // const isDisabled = index + 1 > gridMaxAllowed;
                    const configurationGridTemplateItem = configuratorGridTemplate[item.id];
                    const configurationGridTemplateItemDesk = configuratorGridTemplate[item.id].desk;
                    const configurationGridTemplateItemRotation = configuratorGridTemplate[item.id]?.rotation;
                    let draggableData;
                    let draggableItemComponent;

                    if (configurationGridTemplateItem) {
                        draggableData = {
                            image: configurationGridTemplateItemDesk?.image,
                            name: configurationGridTemplateItemDesk?.name,
                            type: configurationGridTemplateItemDesk?.type,
                            price: {
                                value: configurationGridTemplateItemDesk?.price,
                                currency: configurationGridTemplateItemDesk?.currency,
                            },
                            overlay: configurationGridTemplateItemDesk?.overlay,
                            rotation: configurationGridTemplateItemRotation,
                        };

                        draggableItemComponent = (
                            <Draggable
                                draggableId={uuidv4()}
                                id={configurationGridTemplateItemDesk.id}
                                dropped={true}
                                cellId={item.id}
                                data={draggableData}
                                direction={direction}
                                isDisabled={readOnly}
                            />
                        );
                    }

                    return (
                        <Droppable
                            className={clsx(classes.item, index > gridMaxAllowed && classes.hidden)}
                            key={item.id}
                            id={item.id}
                            direction={direction}
                            type={isDisabled ? CONST.DROPPABLE_TYPE_DISABLED : ((isCustomerTypePublic ? index <= 1 : !index) ? CONST.DROPPABLE_TYPE_DIRECTION : undefined)}
                        >
                            {isCustomerTypePublic ? (
                                index < gridMaxAllowed ? (
                                    configurationGridTemplateItemDesk ? (
                                        draggableItemComponent
                                    ) : title
                                ) : disabled
                            ) : configurationGridTemplateItemDesk ? (
                                draggableItemComponent
                            ) : title}
                        </Droppable>
                    );
                }).toReversed()}
            </div>
            <Mattress
                className={classes.mattress}
                direction={direction}
                count={mattressCount}
                customer={customerType}
            />
        </div>
    );
};

export default memo(Grid);
