'use client';

import clsx from 'clsx';
import React, { BaseSyntheticEvent, memo, useCallback, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import Button from '@/components/button/button';
import DirectionSwitcher from '@/components/directionSwitcher/directionSwitcher';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import Help from '@/components/help/help';
import HistoryControl from '@/components/historyControl/historyControl';
import Input from '@/components/input/input';
import LightBulbSvg from '/public/icons/lightbulb.svg';
import BookOpenCoverSvg from '/public/icons/book-open-cover.svg';
import ImagesSvg from '/public/icons/images.svg';
import Inspiration from '@/components/inspiration/inspiration';
import Manual, { IManualFaq } from '@/components/manual/manual';
import Tooltip from '@/components/tooltip/tooltip';
import { useResizable } from '@/hooks/useResizable';
import { GRID_CONFIG } from '@/lib/grid';
import {
    configuratorIndividualSizeSelector,
    configuratorRealtimeIndividualSizeSelector,
    configuratorSettingsSelector, configuratorStandardSizeSelector,
    configuratorTempDrawerState,
    setDrawerOpen,
    setIndividualAxis, setRealtimeIndividualAxis,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import commonClasses from '@/styles/common.module.scss';
import classes from './settings.module.scss';

export interface ISettingsProps {
    className?: string
    direction: GridDirectionType
    initialData: {
        faq: IManualFaq[],
    }
    readOnly: boolean
}

const Settings = (
    {
        className,
        direction,
        initialData,
        readOnly,
    }: ISettingsProps,
) => {
    const { t, i18n: { language: locale } } = useTranslation();

    const [isManualOpened, setManualOpened] = useState(false);
    const [isInspirationOpened, setInspirationOpened] = useState(false);
    const [isHintOpen, setHintOpen] = useState<boolean>(false);

    const handleHelp = useCallback(() => {
        setHintOpen(prevState => !prevState);
    }, []);

    const dispatch: AppDispatch = useDispatch();
    const configuratorSettings = useSelector(configuratorSettingsSelector);
    const isDrawerOpenedSelector = useSelector(configuratorTempDrawerState);
    const configuratorIndividualSize = useSelector(configuratorIndividualSizeSelector);
    const configuratorStandardSize = useSelector(configuratorStandardSizeSelector);
    const configuratorRealtimeIndividualSize = useSelector(configuratorRealtimeIndividualSizeSelector);

    const { minAllowedWidthCm: minAllowedWidth, minAllowedHeightCm: minAllowedHeight } = useResizable({ direction });

    const gridConfigDirection = GRID_CONFIG[direction];
    const cmMaxX = gridConfigDirection.cmMaxX;
    const cmMaxY = gridConfigDirection.cmMaxY;

    const configuratorRealtimeIndividualSizeAxisX = configuratorRealtimeIndividualSize.axisX;
    const configuratorRealtimeIndividualSizeAxisY = configuratorRealtimeIndividualSize.axisY;

    const limits: {
        [key: string]: { [key: string]: number, },
    } = useMemo(() => ({
        axisX: {
            max: cmMaxX,
            min: minAllowedWidth,
        },
        axisY: {
            max: cmMaxY,
            min: minAllowedHeight,
        },
    }), [cmMaxX, minAllowedWidth, cmMaxY, minAllowedHeight]);

    const [inputs, setInputs] = useState<{ [key: string]: number, }>(configuratorSettings.individual);
    const [inputsError, setInputsError] = useState<{ [key: string]: boolean, }>({
        axisX: false,
        axisY: false,
    });

    useEffect(() => {
        setInputs(configuratorIndividualSize);
    }, [configuratorIndividualSize]);

    useEffect(() => {
        setInputs(prevState => ({
            ...prevState,
            axisX: configuratorRealtimeIndividualSizeAxisX,
        }));
    }, [configuratorRealtimeIndividualSizeAxisX]);

    useEffect(() => {
        setInputs(prevState => ({
            ...prevState,
            axisY: configuratorRealtimeIndividualSizeAxisY,
        }));
    }, [configuratorRealtimeIndividualSizeAxisY]);

    useEffect(() => {
        if (!isDrawerOpenedSelector) {
            if (isManualOpened) setManualOpened(false);
            if (isInspirationOpened) setInspirationOpened(false);
        }
    }, [isDrawerOpenedSelector, isManualOpened, isInspirationOpened]);

    const handleInputsChange = useCallback((event: BaseSyntheticEvent) => {
        const name: string = event.target.name;
        const num: number = Number(event.target.value);
        const sizeLimits = limits[name];

        if (isNaN(num)) return;

        setInputsError(prevState => ({
            ...prevState,
            [name]: num > sizeLimits.max || num < sizeLimits.min,
        }));

        setInputs(prevState => ({
            ...prevState,
            [name]: num,
        }));
    }, [limits]);

    const handleInputsBlur = useCallback((event: BaseSyntheticEvent) => {
        const name: string = event.target.name;
        const num: number = Number(event.target.value);
        const sizeLimits = limits[name];

        if (num > sizeLimits.max || num < sizeLimits.min) return;

        dispatch(setIndividualAxis( {
            [name]: num,
        } ));
        dispatch(setRealtimeIndividualAxis( {
            [name]: num,
        } ));
    }, [dispatch, limits]);

    const handleInputsKeyPress = useCallback((event: BaseSyntheticEvent) => {
        if (event.keyCode === 13) {
            const name: string = event.target.name;
            const num: number = Number(event.target.value);
            const sizeLimits = limits[name];

            if (num > sizeLimits.max || num < sizeLimits.min) return;

            dispatch(setIndividualAxis( {
                [name]: num,
            } ));
            dispatch(setRealtimeIndividualAxis( {
                [name]: num,
            } ));
        }
    }, [dispatch, limits]);

    return (
        <div className={clsx(classes.root, className)}>
            <div className={clsx(commonClasses.container, classes.container)}>
                <div className={classes.grid}>
                    <div className={classes.item}>
                        {!readOnly && <HistoryControl direction={direction} />}
                    </div>
                    <div className={clsx(classes.item, ['en', 'de'].includes(locale) && classes.smaller)}>
                        <span>{t('settings:orientation')}</span>
                        <DirectionSwitcher
                            readOnly={readOnly}
                        />
                    </div>
                    <div className={clsx(classes.item, ['en', 'de'].includes(locale) && classes.smaller)}>
                        <span>{t('settings:standardSizes')}</span>
                        <div className={classes.inputs}>
                            <Input
                                className={classes.cm}
                                inputClassName={classes.input}
                                id={'width'}
                                label={t('settings:width')}
                                type={'text'}
                                value={configuratorSettings.standard.axisY}
                                disabled={true}
                            />
                            <Input
                                className={classes.cm}
                                inputClassName={classes.input}
                                id={'height'}
                                label={t('settings:height')}
                                type={'text'}
                                value={configuratorSettings.standard.axisX}
                                disabled={true}
                            />
                        </div>
                    </div>
                    <div className={clsx(classes.item, ['en', 'de'].includes(locale) && classes.smaller)}>
                        <span>{t('settings:individualSizes')}</span>
                        <div className={classes.inputs}>
                            <div className={classes.control}>
                                <Input
                                    className={clsx(classes.cm, inputsError.axisX && classes.error, inputs.axisX < configuratorStandardSize.axisX && !inputsError.axisX && classes.individual)}
                                    inputClassName={clsx(classes.input, classes.border)}
                                    labelClassName={clsx(classes.inputLabel)}
                                    id={'axisX'}
                                    label={t('settings:axisX')}
                                    type={'text'}
                                    value={inputs.axisX}
                                    onChange={handleInputsChange}
                                    onBlur={handleInputsBlur}
                                    onKeyUp={handleInputsKeyPress}
                                    error={inputsError.axisX}
                                    disabled={readOnly}
                                />
                                {inputsError.axisX &&
                                    <span className={classes.errorMessage}>{t('messages:minMaxAllowedWidth', {
                                        min: minAllowedWidth,
                                        max: gridConfigDirection.cmMaxX,
                                    })}</span>
                                }
                            </div>
                            <div className={classes.control}>
                                <Input
                                    className={clsx(classes.cm, inputsError.axisY && classes.error, inputs.axisY < configuratorStandardSize.axisY && !inputsError.axisY && classes.individual)}
                                    inputClassName={clsx(classes.input, classes.border)}
                                    labelClassName={clsx(classes.inputLabel)}
                                    id={'axisY'}
                                    label={t('settings:axisY')}
                                    type={'text'}
                                    value={inputs.axisY}
                                    onChange={handleInputsChange}
                                    onBlur={handleInputsBlur}
                                    onKeyUp={handleInputsKeyPress}
                                    error={inputsError.axisY}
                                    disabled={readOnly}
                                />
                                {inputsError.axisY &&
                                    <span className={classes.errorMessage}>{t('messages:minMaxAllowedHeight', {
                                        min: minAllowedHeight,
                                        max: gridConfigDirection.cmMaxY,
                                    })}</span>
                                }
                            </div>
                        </div>
                        <div className={classes.icon}>
                            <Tooltip
                                className={classes.tooltip}
                                text={t('settings:tooltipHelp')}
                            >
                                <div className={classes.svg}>
                                    <LightBulbSvg
                                        onClick={handleHelp}
                                    />
                                </div>
                            </Tooltip>
                        </div>
                    </div>
                </div>
                <div className={classes.buttons}>
                    <Button
                        className={classes.button}
                        theme={'secondary'}
                        onClick={() => {
                            setManualOpened(true);
                            dispatch(setDrawerOpen(true));
                        }}
                    >
                        <BookOpenCoverSvg/>
                        {t('settings:guide')}
                    </Button>
                    <Button
                        className={classes.button}
                        theme={'secondary'}
                        onClick={() => {
                            setInspirationOpened(true);
                            dispatch(setDrawerOpen(true));
                        }}
                    >
                        <ImagesSvg/>
                        {t('settings:hints')}
                    </Button>
                </div>
            </div>
            {isDrawerOpenedSelector && isInspirationOpened && createPortal(
                <Inspiration />,
                document?.getElementById('drawer') as Element,
            )}
            {isDrawerOpenedSelector && isManualOpened && createPortal(
                <Manual
                    initialData={initialData.faq}
                />,
                document?.getElementById('drawer') as Element,
            )}
            {isHintOpen && createPortal(
                <Help
                    title={t('help:title')}
                    description={t('help:axis')}
                    onClose={handleHelp}
                />,
                document?.getElementById('grid-template') as Element,
            )}
        </div>
    );
};

export default memo(Settings);
