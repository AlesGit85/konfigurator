import clsx from 'clsx';
import { memo, useCallback } from 'react';
import RotateSvg from '/public/icons/arrows-rotate.svg';
import GrabSvg from '/public/icons/grab-solid.svg';
import TrashSvg from '/public/icons/trash-solid.svg';
import { useDispatch } from 'react-redux';
import Button from '@/components/button/button';
import * as CONST from '@/lib/constants';
import {
    recalculateDraft,
    setGrid, setHistory, setStandardAxis, updateMattressPrice, updateOverlay,
    updateRotation,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import classes from '../../draggable.module.scss';

export interface IControlProps {
    className: string
    identifier: string
    data: {
        deskType: string,
    }
}

const Control = (
    {
        className,
        identifier,
        data,
        ...otherProps
    }: IControlProps,
) => {
    const dispatch: AppDispatch = useDispatch();

    const handleDelete = useCallback(() => {
        dispatch(setGrid({
            source: {
                cellId: identifier,
                value: '',
            },
        }));
        dispatch(setStandardAxis());
        //dispatch(recalculateDraftByGridAction({ type: getDeskType(data?.deskType) }));
        dispatch(recalculateDraft());
        dispatch(updateMattressPrice());
        setTimeout(() => { dispatch(setHistory()); });
    }, [dispatch, identifier, data?.deskType]);

    const handleRotate = useCallback(() => {
        dispatch(updateRotation({
            cellId: identifier,
        }));
        dispatch(updateOverlay( { isRemove: false, isOverlayChange: true } ));
        setTimeout(() => { dispatch(setHistory()); });
    }, [dispatch, identifier]);

    return (
        <div className={clsx(classes.buttons, className)}>
            <Button
                className={classes.action}
                theme={CONST.THEME_TERTIARY}
                onClick={handleRotate}
            >
                <RotateSvg
                    className={classes.icon}
                />
            </Button>
            <Button
                className={clsx(classes.action, classes.grab)}
                theme={CONST.THEME_TERTIARY}
                {...otherProps}
            >
                <GrabSvg
                    className={classes.icon}
                />
            </Button>
            <Button
                className={classes.action}
                theme={CONST.THEME_TERTIARY}
                onClick={handleDelete}
            >
                <TrashSvg
                    className={classes.icon}
                />
            </Button>
        </div>
    );
};

export default memo(Control);
