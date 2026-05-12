'use client';

import clsx from 'clsx';
import Image from 'next/image';
import React, { memo, useCallback, useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import Checkbox from '@/components/checkbox/checkbox';
import Tooltip from '@/components/tooltip/tooltip';
import * as CONST from '@/lib/constants';
import { configuratorCustomerTypeSelector } from '@/redux/slices/configuratorSlice';
import classes from './accessories.module.scss';

const HOLDS = CONST.ACCESSORY_TYPE_HOLDS;
const MATTRESS = CONST.ACCESSORY_TYPE_MATTRESS;
const PUBLIC = CONST.CUSTOMER_TYPE_PUBLIC;
const FAMILY = CONST.CUSTOMER_TYPE_FAMILY;

export interface IAccessoriesProps {
    className?: string
    iconClassName?: string
    id: string
    icons: {
        id: number,
        title: string,
        image: string,
        color: string,
        price: number,
        currency: string,
    }[]
    title: string
    description: string
    selected: object
    onChange?: () => void
    onSelect?: (item) => void
}

const Accessories = (
    {
        className,
        iconClassName,
        id,
        icons,
        title,
        description,
        selected,
        onChange,
        onSelect,
    }: IAccessoriesProps,
) => {
    const [checked, setChecked] = useState(!!selected?.id);

    const customerType = useSelector(configuratorCustomerTypeSelector);
    const isPriceArray: boolean = customerType === PUBLIC && id === MATTRESS;

    useEffect(() => {
        setChecked(!!selected?.id);
    }, [selected?.id]);

    const handleSelect = useCallback((id: number, title: string, image: string, color: string, price: number, currency: string, personal: boolean, overlays: object[]) => {
        if (selected?.id === id) {
            onSelect?.({});
        } else {
            onSelect?.({
                id,
                title,
                image,
                color,
                price,
                currency,
                personal,
                overlays,
            });
        }
        setChecked(prevState => !prevState);
    }, [selected, onSelect]);

    const handleChange = useCallback(() => {
        onChange?.();
        setChecked(prevState => !prevState);
    }, [onChange]);

    return (
        <div className={clsx(classes.root, className)}>
            <div className={classes.checkbox}>
                <Checkbox
                    id={id}
                    checked={checked}
                    onChange={handleChange}
                    // className={clsx(!checked && classes.disabled)}
                >
                    {title}
                </Checkbox>
            </div>
            <div className={classes.content}>
                <div className={classes.icons}>
                    {icons?.map((item) => {
                        const isSelected = selected?.id === item.id;
                        const styles = {
                            ...(item?.color && { backgroundColor: item?.color }),
                        };

                        const iconItemComponent = (
                            <Tooltip
                                key={item.id}
                                className={classes.tooltip}
                                text={item.title}
                            >
                                <div
                                    className={clsx(classes.icon, isSelected && classes.selected, iconClassName)}
                                    style={styles}
                                    onClick={() => handleSelect(item.id, item?.title, item?.image, item?.color, isPriceArray ? item?.prices : item?.price, item?.currency, item?.personal, item?.overlays)}
                                >
                                    {item?.image &&
                                        <Image
                                            className={classes.image}
                                            src={item?.image}
                                            alt={item?.title || ''}
                                            width={48}
                                            height={48}
                                        />
                                    }
                                </div>
                            </Tooltip>
                        );

                        if (id === MATTRESS) {
                            if ((customerType === PUBLIC && !item?.personal) || (customerType === FAMILY && item?.personal)) {
                                return iconItemComponent;
                            }
                        }

                        if (id === HOLDS) {
                            return iconItemComponent;
                        }
                    })}

                </div>
                <div className={classes.description}>
                    {description}
                </div>
            </div>
        </div>
    );
};

export default memo(Accessories);
