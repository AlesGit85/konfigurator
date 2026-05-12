'use client';

import { Flip, ToastContainer } from 'react-toastify';
import ToastCheckSvg from '/public/icons/circle-check-solid.svg';
import ToastFaceFrownSvg from '/public/icons/face-frown-slight-solid.svg';

import 'react-toastify/dist/ReactToastify.css';

import classes from './notify.module.scss';
import clsx from 'clsx';

export interface IToastContainerWrapperProps {
    className?: string
    containerId: string
}

export default function ToastContainerWrapper(
    {
        className,
        containerId,
    }: IToastContainerWrapperProps,
) {
    return (
        <ToastContainer
            className={clsx(classes.root, className)}
            position="bottom-center"
            autoClose={3000}
            hideProgressBar={true}
            newestOnTop={false}
            closeOnClick={true}
            rtl={false}
            pauseOnFocusLoss={false}
            draggable={false}
            pauseOnHover={true}
            transition={Flip}
            containerId={containerId}
            icon={({ type }) => {
                if (type === 'success') return <ToastCheckSvg />;
                if (type === 'error') return <ToastFaceFrownSvg />;
                return undefined;
            }}
        />
    );
}
