import clsx from 'clsx';
import { BaseSyntheticEvent, memo, useCallback, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import HelpControl from '@/components/help/control/control';
import Help from '@/components/help/help';
import Orientation from '@/components/icon/orientation/orientation';
import Radio from '@/components/radio/radio';
import * as CONST from '@/lib/constants';
import {
    configuratorCustomerTypeSelector,
    configuratorDraftDirectionSelector,
    resetAccessories,
    resetDraftControl,
    resetGrid, resetHistory,
    resetIndividualAxis, resetRealtimeIndividualAxis,
    resetStandardAxis,
    setDraftDirection,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import classes from './directionSwitcher.module.scss';

export interface IDirectionSwitcherProps {
    className?: string
    readOnly: boolean
}

const VERTICAL = CONST.GRID_ALIGNMENT_VERTICAL;
const HORIZONTAL = CONST.GRID_ALIGNMENT_HORIZONTAL;

const DirectionSwitcher = (
    {
        className,
        readOnly,
    }: IDirectionSwitcherProps,
) => {
    const { t } = useTranslation();

    const direction = useSelector(configuratorDraftDirectionSelector);
    const customerType = useSelector(configuratorCustomerTypeSelector);

    const dispatch: AppDispatch = useDispatch();

    const [alert, setAlert] = useState({
        isOpen: false,
        data: '',
    });

    const handleHelp = useCallback((isOpen: boolean, data?: string): void => setAlert({ isOpen, data: data || '' }), []);

    const handleHelpClose = useCallback(() => handleHelp(false), [handleHelp]);

    const handleRadioChange = useCallback((e: BaseSyntheticEvent) => {
        handleHelp(true, e.target.value);
    }, [handleHelp]);

    const handleProceed = useCallback(() => {
        dispatch(resetGrid());
        dispatch(resetDraftControl());
        dispatch(resetAccessories());
        dispatch(resetIndividualAxis());
        dispatch(resetRealtimeIndividualAxis());
        dispatch(resetStandardAxis());
        dispatch(setDraftDirection(alert?.data));
        dispatch(resetHistory());
        handleHelp(false);
    }, [dispatch, handleHelp, alert]);

    const HelpControlComponent = useMemo(() => () => (
        <HelpControl
            onProceed={handleProceed}
            onClose={handleHelpClose}
        />
    ), [handleProceed, handleHelpClose]);

    return (
        <div className={clsx(classes.root, className)}>
            <Radio
                id="radio-item-1"
                value={VERTICAL}
                label={
                    <Orientation
                        direction={VERTICAL}
                    />
                }
                onChange={handleRadioChange}
                checked={direction === VERTICAL}
                isDisabled={readOnly}
            />
            {customerType === CONST.CUSTOMER_TYPE_FAMILY &&
                <Radio
                    id="radio-item-2"
                    value={HORIZONTAL}
                    label={
                        <Orientation
                            direction={HORIZONTAL}
                        />
                    }
                    onChange={handleRadioChange}
                    checked={direction === HORIZONTAL}
                    isDisabled={readOnly}
                />
            }
            {alert?.isOpen && createPortal(
                <Help
                    title={t('settings:alertTitle')}
                    description={t('settings:alertDescription')}
                    onClose={handleHelpClose}
                    customControlComponent={<HelpControlComponent />}
                />,
                document?.getElementById('grid-template') as Element,
            )}
        </div>
    );
};

export default memo(DirectionSwitcher);
