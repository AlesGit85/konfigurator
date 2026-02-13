'use client';

import clsx from 'clsx';
import CloseSvg from '/public/icons/close.svg';
import { memo, useCallback, useEffect } from 'react';
import ClickAwayListener from 'react-click-away-listener';
import { useDispatch, useSelector } from 'react-redux';
import { configuratorTempDrawerState, setDrawerOpen } from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import classes from './drawer.module.scss';

export interface IDrawerProps {
    id: string
    children?: JSX.Element
}

const Drawer = (
    {
        id,
        children,
    }: IDrawerProps,
) => {
    const dispatch: AppDispatch = useDispatch();
    const isDrawerOpenedSelector = useSelector(configuratorTempDrawerState);

    useEffect(() => {
        if (isDrawerOpenedSelector) {
            document.body.classList.add('blur');
        } else {
            document.body.classList.remove('blur');
        }
    }, [isDrawerOpenedSelector]);

    const handleClickAway = useCallback((e) => {
        if (isDrawerOpenedSelector && !e.target.id.includes('fancybox')) {
            dispatch(setDrawerOpen(false));
        }
    }, [dispatch, isDrawerOpenedSelector]);

    return (
        <div
            className={clsx(classes.root, isDrawerOpenedSelector && classes.show)}
        >
            <ClickAwayListener
                onClickAway={handleClickAway}
            >
                <div
                    id={id}
                    className={classes.content}
                >
                    {children}
                    <button
                        className={classes.close}
                        onClick={handleClickAway}
                    >
                        <CloseSvg/>
                    </button>
                </div>
            </ClickAwayListener>
        </div>
    );
};

export default memo(Drawer);
