import clsx from 'clsx';
import { dir } from 'i18next';
import { memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import UndoSvg from '/public/icons/arrow-rotate-left-regular.svg';
import RedoSvg from '/public/icons/arrow-rotate-right-regular.svg';

import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import Button from '@/components/button/button';
import Tooltip from '@/components/tooltip/tooltip';
import * as CONST from '@/lib/constants';
import {
    configuratorHistorySelector, configuratorHoldListSelector, configuratorMattressListSelector,
    decreaseHistoryPosition,
    increaseHistoryPosition, resetGrid, setAccessory,
    setDraftList, setHistory, setIndividualAxis,
    setInitialAccessory,
    setInitialGrid, setRealtimeIndividualAxis, setStandardAxis, updateOverlay,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import classes from './historyControl.module.scss';

export interface IHistoryControlProps {
    className?: string
    direction: string
}

const HistoryControl = (
    {
        className,
        direction,
    }: IHistoryControlProps,
) => {
    const { t } = useTranslation();

    const dispatch: AppDispatch = useDispatch();

    const configuratorHistory = useSelector(configuratorHistorySelector);
    const configuratorHoldList = useSelector(configuratorHoldListSelector);
    const configuratorMattressList = useSelector(configuratorMattressListSelector);

    const mountedRef = useRef<boolean>(false);
    const isHistoryActiveRef = useRef<boolean>(false);

    const currentPosition: number = configuratorHistory.currentPosition;
    const totalPosition: number = configuratorHistory.totalPosition;
    const currentHistory = configuratorHistory.list;

    const isBackDisabled: boolean = currentPosition <= 1;
    const isForwardDisabled: boolean = currentPosition >= currentHistory.length;

    const updateConfigurator = useCallback((position: number) => {
        dispatch(resetGrid());
        dispatch(setInitialGrid({
            targets: currentHistory[position].workspace,
            direction: direction,
        }));
        dispatch(setAccessory({
            hold: configuratorHoldList.find((hold: { id: number, }) => currentHistory[position].grip === hold.id) || {},
            mattress: configuratorMattressList.find((mattress: { id: number, }) => currentHistory[position].mattress === mattress.id) || {},
        }));
        dispatch(setIndividualAxis({
            axisX: currentHistory[position].customWidth,
            axisY: currentHistory[position].customHeight,
        }));
        dispatch(setRealtimeIndividualAxis({
            axisX: currentHistory[position].customWidth,
            axisY: currentHistory[position].customHeight,
        }));
        dispatch(updateOverlay( { isRemove: false, isOverlayChange: true } ));
        dispatch(setStandardAxis());
    }, [dispatch, currentHistory, direction, configuratorHoldList, configuratorMattressList]);

    useEffect(() => {
        if (!mountedRef.current) {
            dispatch(setHistory());
            mountedRef.current = true;
        }
    }, []);

    const handleBack = () => {
        dispatch(decreaseHistoryPosition());
        updateConfigurator(currentPosition - 2);
        if (!isHistoryActiveRef.current) isHistoryActiveRef.current = true;
    };

    const handleForward = () => {
        dispatch(increaseHistoryPosition());
        updateConfigurator(currentPosition);
        if (!isHistoryActiveRef.current) isHistoryActiveRef.current = true;
    };

    return (
        <div className={clsx(classes.root, className)}>
            <Tooltip
                text={t('settings:undo')}
            >
                <Button
                    className={clsx(classes.button, classes.icon, classes.undo)}
                    theme={CONST.THEME_TRANSPARENT}
                    onClick={handleBack}
                    isDisabled={isBackDisabled}
                >
                    <UndoSvg />
                </Button>
            </Tooltip>
            <Tooltip
                text={t('settings:redo')}
            >
                <Button
                    className={clsx(classes.button, classes.icon, classes.redo)}
                    theme={CONST.THEME_TRANSPARENT}
                    onClick={handleForward}
                    isDisabled={isForwardDisabled}
                >
                    <RedoSvg />
                </Button>
            </Tooltip>
        </div>
    );
};

export default memo(HistoryControl);
