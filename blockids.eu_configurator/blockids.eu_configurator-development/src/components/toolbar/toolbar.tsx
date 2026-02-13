'use client';

import clsx from 'clsx';
import { memo, useCallback, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import Accessories from '@/components/accessories/accessories';
import Boards from '@/components/boards/boards';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import * as CONST from '@/lib/constants';
import {
    configuratorCustomerTypeSelector,
    configuratorHoldsSelector,
    configuratorMattressSelector,
    setHistory,
    recalculateDraftByAccessoryAction,
    setAccessory,
    updateOverlay,
    configuratorIndividualSizeSelector,
    configuratorStandardSizeSelector,
    updateMattressPrice,
} from '@/redux/slices/configuratorSlice';
import commonClasses from '@/styles/common.module.scss';
import classes from './toolbar.module.scss';

export type ToolbarAccessoriesType = typeof CONST.ACCESSORY_TYPE_MATTRESS | typeof CONST.ACCESSORY_TYPE_HOLDS

export interface IToolbarProps {
    boards: {
        direction: GridDirectionType,
        data: object[],
    }
    holds: {
        data: object[],
        isHidden?: boolean,
    }
    mattresses: {
        data: object[],
        isHidden?: boolean,
    }
    readOnly: boolean
}

const HOLDS = CONST.ACCESSORY_TYPE_HOLDS;
const MATTRESS = CONST.ACCESSORY_TYPE_MATTRESS;
const PUBLIC = CONST.CUSTOMER_TYPE_PUBLIC;

const Toolbar = (
    {
        boards,
        holds,
        mattresses,
        readOnly,
    }: IToolbarProps,
) => {
    const { t } = useTranslation();

    const dispatch = useDispatch();

    const configuratorStandardSize = useSelector(configuratorStandardSizeSelector);
    const configuratorIndividualSize = useSelector(configuratorIndividualSizeSelector);
    const configuratorHoldSelected = useSelector(configuratorHoldsSelector);
    const configuratorMattressSelected = useSelector(configuratorMattressSelector);
    const customerType = useSelector(configuratorCustomerTypeSelector);

    const handleAccessoryChange = useCallback((type: ToolbarAccessoriesType, item: any) => {
        dispatch(setAccessory({
            [type]: item,
        }));

        dispatch(recalculateDraftByAccessoryAction({
            type,
            price: item.price,
        }));
        dispatch(updateMattressPrice());
        setTimeout(() => { dispatch(setHistory()); });
    }, [configuratorStandardSize.axisX, customerType]);

    /**
     * on checkbox change - first item in array is set
     */
    const handleChange = useCallback((type: ToolbarAccessoriesType) => {
        const isPriceArray: boolean = customerType === PUBLIC && type === MATTRESS;

        const holdsItem = !!configuratorHoldSelected?.id ? {} : {
            id: holds?.data[0].id,
            title: holds?.data[0].title,
            image: holds?.data[0].image,
            price: holds?.data[0].price,
            currency: holds?.data[0].currency,
            overlays: holds.data[0].overlays,
        };

        const mattressItem = !!configuratorMattressSelected?.id ? {} : {
            id: mattresses?.data[0].id,
            title: mattresses?.data[0].title,
            image: mattresses?.data[0].image,
            color: mattresses?.data[0].color,
            price: isPriceArray ? mattresses?.data[0].prices : mattresses?.data[0].price,
            currency: mattresses?.data[0].currency,
            personal: mattresses?.data[0].personal,
        };

        if (type === MATTRESS) {
            handleAccessoryChange(type, mattressItem);
        }

        if (type === HOLDS) {
            handleAccessoryChange(type, holdsItem);
        }
    }, [mattresses, holds, handleAccessoryChange, configuratorHoldSelected, configuratorMattressSelected]);

    return (
        <footer className={classes.root}>
            <div className={clsx(commonClasses.container, classes.container)}>
                <Boards
                    className={classes.boards}
                    direction={boards.direction}
                    data={boards?.data}
                    readOnly={readOnly}
                />
                <div className={classes.extras}>
                    {!holds.isHidden &&
                        <Accessories
                            className={clsx(readOnly && classes.disabled)}
                            iconClassName={classes.rounded}
                            id={HOLDS}
                            title={t('toolbar:holdsTitle')}
                            icons={holds?.data}
                            description={t('toolbar:holdsDescription')}
                            selected={configuratorHoldSelected}
                            onSelect={(item) => handleAccessoryChange(HOLDS, item)}
                            onChange={() => handleChange(HOLDS)}
                        />
                    }
                    {!mattresses.isHidden &&
                        <Accessories
                            className={clsx(readOnly && classes.disabled)}
                            id={MATTRESS}
                            title={t('toolbar:mattressTitle')}
                            icons={mattresses?.data}
                            description={t(`toolbar:mattressDescription-${customerType}`)}
                            selected={configuratorMattressSelected}
                            onSelect={(item) => handleAccessoryChange(MATTRESS, item)}
                            onChange={() => handleChange(MATTRESS)}
                        />
                    }
                </div>
            </div>
        </footer>
    );
};

export default memo(Toolbar);
