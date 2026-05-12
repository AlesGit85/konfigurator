'use client';

import clsx from 'clsx';
import { memo, useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import * as CONST from '@/lib/constants';
import { GRID_CONFIG } from '@/lib/grid';
import { convert } from '@/lib/utils';
import {
    configuratorCustomerTypeSelector, configuratorDraftControlSelector,
    configuratorMattressSelector, configuratorSettingsSelector,
    updateAccessoryCount,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import { CustomerType } from '@/redux/types/configuratorTypes';
import classes from './mattress.module.scss';

export interface IMattressProps {
    className?: string
    direction: GridDirectionType
    customer: CustomerType
    count: number
}

const Mattress = (
    {
        className,
        direction,
        customer,
        count,
    }: IMattressProps,
) => {
    const { t } = useTranslation();

    const dispatch: AppDispatch = useDispatch();

    const OVER = useMemo(() => t('grid:mattressOver'), [t]);

    const customerType = useSelector(configuratorCustomerTypeSelector);
    const configuratorMattress = useSelector(configuratorMattressSelector);
    const configuratorDraftControl = useSelector(configuratorDraftControlSelector);
    const configuratorSettings = useSelector(configuratorSettingsSelector);
    const gridConfigDirection = GRID_CONFIG[direction];
    const configuratorIsIndividualSize = !!configuratorDraftControl.extraSize.count;
    const configuratorMattressPersonal = configuratorMattress?.personal;
    const configuratorMattressColor = configuratorMattress?.color;
    const isMattressAllowed = !!configuratorMattress?.id;
    const mattressCount = !configuratorMattressPersonal && count === 1 ? count + 1 : count;
    const dummyArr = Array.from({ length: mattressCount });

    useEffect(() => {
        if (customerType === CONST.CUSTOMER_TYPE_PUBLIC) return;
        isMattressAllowed && dispatch(updateAccessoryCount( { type: CONST.ACCESSORY_TYPE_MATTRESS, count, price: configuratorMattress.price } ));
    }, [dispatch, count, configuratorMattress]);

    useEffect(() => {

    }, []);

    const backgroundColorStyles = {
        ...(configuratorMattressColor && { backgroundColor: configuratorMattressColor }),
    };

    return (
        <div className={clsx(classes.root, direction && classes[direction], customer && classes[customer], className)}>
            {count > 0 && isMattressAllowed &&
                <div
                    className={classes.container}
                    style={{
                        ...(!configuratorMattressPersonal && backgroundColorStyles),
                    }}
                    {...(!configuratorMattressPersonal && count < 3 && {
                        'data-text': t('grid:mattressMinText'),
                    })}
                >
                    {dummyArr.map((_, index) => {
                        let mattressItemWidth = gridConfigDirection.mattress[customerType];

                        if (!configuratorMattressPersonal) {
                            // do the size calculation only if is larger then min
                            if (index > 1) {
                                if (configuratorIsIndividualSize && (dummyArr.length === (index + 1)) && configuratorSettings.realtime.individual.axisX < configuratorSettings.standard.axisX) {
                                    const deficit: number = convert(configuratorSettings.realtime.individual.axisX - configuratorSettings.standard.axisX, gridConfigDirection.pixelToCmX);
                                    mattressItemWidth = gridConfigDirection.mattress[customerType] + deficit;
                                }
                            }
                        }

                        const styles = {
                            width: mattressItemWidth,
                            ...(configuratorMattressPersonal && backgroundColorStyles),
                        };

                        return (
                            <div
                                key={index}
                                className={classes.item}
                                style={styles}
                            >
                                {!configuratorMattressPersonal &&
                                    [
                                        <div
                                            key={'before'}
                                            className={classes.before}
                                            style={backgroundColorStyles}
                                        >
                                            {OVER}
                                        </div>,
                                        <div
                                            key={'after'}
                                            className={classes.after}
                                            style={backgroundColorStyles}
                                        >
                                            {OVER}
                                        </div>,
                                    ]
                                }
                            </div>
                        );
                    })}
                </div>
            }
        </div>
    );
};

export default memo(Mattress);
