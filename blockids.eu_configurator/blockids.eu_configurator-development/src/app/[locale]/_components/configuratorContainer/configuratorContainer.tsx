'use client';

import clsx from 'clsx';
import dynamic from 'next/dynamic';
import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { updateSessionAction } from '@/action/session/updateSessionAction';
import classes from '@/app/[locale]/page.module.css';
import Grid from '@/components/dnd/grid/grid';
import DraftControl from '@/components/draftControl/draftControl';
import Main from '@/components/main/main';
import { IManualFaq } from '@/components/manual/manual';
import Settings from '@/components/settings/settings';
import Spinner from '@/components/spinner/spinner';
import Toolbar from '@/components/toolbar/toolbar';
import * as CONST from '@/lib/constants';
import { getCustomerType, getDirection } from '@/lib/utils';
import {
    configuratorDraftDirectionSelector,
    recalculateDraftByAccessoryAction,
    setAccessory,
    setCustomerType,
    setDraftDirection,
    setDraftList,
    setIndividualAxis,
    setInitialAccessory,
    setInitialGrid,
    setInitialHoldList,
    setInitialMattressList,
    setLocationType,
    setRealtimeIndividualAxis,
    setStandardAxis,
    updateMattressPrice,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import commonClasses from '@/styles/common.module.scss';

const DndContextLazy = dynamic(
    () => import('@dnd-kit/core').then(module => (module.DndContext),
    ), {
        ssr: false,
        loading: () => <Spinner />,
    });

export interface IConfiguratorContainerProps {
    customerType: number | undefined
    locationType: string | undefined
    initialData: {
        hash: string,
        title: string | null,
        direction: string,
        deskList: object[],
        draftList: object[],
        grid: object,
        standard: {
            axisX: number,
            axisY: number,
        },
        individual: {
            axisX: number,
            axisY: number,
        },
        hold: {
            selected: object,
            list: object[],
        },
        mattress: {
            selected: object,
            list: object[],
        },
        faq: IManualFaq[],
    }
    readOnly: boolean
}

const ConfiguratorContainer = (
    {
        customerType,
        locationType,
        initialData,
        readOnly,
    }: IConfiguratorContainerProps,
) => {
    // const DIRECTION = CONST.GRID_ALIGNMENT_VERTICAL;
    // const DIRECTION = CONST.GRID_ALIGNMENT_HORIZONTAL;

    const dispatch: AppDispatch = useDispatch();

    const direction = useSelector(configuratorDraftDirectionSelector);

    useEffect(() => {
        const setInitialHashSession = async(): Promise<void> => {
            await updateSessionAction(CONST.DRAFT_ID_HASH, initialData.hash);
        };
        const translatedCustomerType: string = getCustomerType(customerType);

        dispatch(setCustomerType(translatedCustomerType));
        dispatch(setLocationType(locationType));
        setInitialHashSession();
    }, [dispatch, customerType, locationType]);

    useEffect(() => {
        const translatedDirection = getDirection(initialData.direction);
        dispatch(setDraftDirection(translatedDirection));
    }, [dispatch, initialData.direction]);

    useEffect(() => {
        // initial workspace grid
        dispatch(setInitialGrid({
            targets: initialData.grid,
            direction: initialData.direction,
        }));

        dispatch(setInitialAccessory({
            hold: initialData.hold.selected,
            mattress: initialData.mattress.selected,
        }));

        // initial stored drafts by user
        dispatch(setDraftList(initialData.draftList));
        dispatch(setInitialHoldList(initialData.hold.list));
        dispatch(setInitialMattressList(initialData.mattress.list));

        dispatch(setStandardAxis());

        dispatch(setIndividualAxis(initialData.individual));
        dispatch(setRealtimeIndividualAxis(initialData.standard));

        if(initialData?.mattress?.selected) {
            const isPriceArray: boolean = customerType == 2;

            const selectedMattressItem = initialData?.mattress?.selected;

            const type = CONST.ACCESSORY_TYPE_MATTRESS;
            const item = {
                id: selectedMattressItem.id,
                title: selectedMattressItem.title,
                image: selectedMattressItem.image,
                color: selectedMattressItem.color,
                price: isPriceArray ? selectedMattressItem.prices : selectedMattressItem.price,
                currency: selectedMattressItem.currency,
                personal: selectedMattressItem.personal,
            };

            dispatch(setAccessory({
                [type]: item,
            }));

            dispatch(recalculateDraftByAccessoryAction({
                type,
                price: item.price,
            }));

            dispatch(updateMattressPrice());
        }
    }, []);

    return (
        <DndContextLazy>
            <Main className={classes.main}>
                <Settings
                    className={classes.settings}
                    direction={direction}
                    initialData={{
                        faq: initialData.faq,
                    }}
                    readOnly={readOnly}
                />
                <div className={clsx(classes.content, commonClasses.container)}>
                    <Grid
                        direction={direction}
                        initialData={{
                            size: {
                                individual: initialData.individual,
                            },
                            direction: initialData.direction,
                        }}
                        readOnly={readOnly}
                    />
                    <DraftControl
                        initialData={{
                            title: initialData?.title,
                        }}
                        readOnly={readOnly}
                        locationType={locationType}
                    />
                </div>
            </Main>
            <Toolbar
                boards={{
                    direction,
                    data: initialData.deskList,
                }}
                holds={{
                    data: initialData.hold.list,
                }}
                mattresses={{
                    data: initialData.mattress.list,
                    isHidden: locationType === CONST.LOCATION_TYPE_OUTDOOR,
                }}
                readOnly={readOnly}
            />
        </DndContextLazy>
    );
};

export default ConfiguratorContainer;
