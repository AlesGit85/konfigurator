'use client';

import LogoSvg from '/public/logo.svg';
import UserSvg from '/public/icons/user.svg';
import LogoutSvg from '/public/icons/logout.svg';
import Button from '@/components/button/button';
import FilePlusSvg from '/public/icons/file-plus.svg';
import commonClasses from '@/styles/common.module.scss';
import classes from './header.module.scss';
import { useTranslation } from 'react-i18next';
import clsx from 'clsx';
import * as CONST from '@/lib/constants';
import { memo, useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'react-toastify';
import { saveDraftAction } from '@/action/saveDraftAction';
import { useDraft } from '@/hooks/useDraft';
import { SaveDraftPayloadType } from '@/app/api/_lib/endpoints';
import {
    configuratorTempDrawerState,
    setDrawerOpen,
} from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import { useDispatch, useSelector } from 'react-redux';
import { createPortal } from 'react-dom';
import MyAccount from '@/components/myAccount/myAccount';
import CartAdd from '@/components/cartAdd/cartAdd';
import { logoutAction } from '@/action/session/logoutAction';
import Tooltip from '@/components/tooltip/tooltip';
import Help from '@/components/help/help';
import CustomHelpControl from '@/components/help/customControl/customControl';
import Modal from '@/components/modal/modal';

export interface IHeaderProps {
    currentDraftId: string
    fullName?: string
    email?: string
    phone?: string
    readOnly: boolean
}

const Header = (
    {
        currentDraftId,
        fullName,
        email,
        phone,
        readOnly,
    }: IHeaderProps,
) => {
    const { t } = useTranslation();

    const { createDraft, getPayload } = useDraft();

    const [saveLoading, setSaveLoading] = useState(false);
    const [isMyAccountOpened, setMyAccountOpened] = useState(false);
    const [open, setOpen] = useState<boolean>(false);
    const [modalOpen, setModalOpen] = useState<boolean>(false);

    const dispatch: AppDispatch = useDispatch();
    const isDrawerOpenedSelector = useSelector(configuratorTempDrawerState);

    useEffect(() => {
        if (!isDrawerOpenedSelector) {
            if (isMyAccountOpened) setMyAccountOpened(false);
        }
    }, [isDrawerOpenedSelector, isMyAccountOpened]);

    const handleHelp = useCallback((isOpen: boolean): void => setOpen(isOpen), []);
    const handleHelpClose = useCallback(() => handleHelp(false), [handleHelp]);

    const handleLogout = useCallback(async() => {
        await logoutAction();
    }, []);

    const handleSaveDraftButtonClick = useCallback(async() => {
        setSaveLoading(true);
        const payload: SaveDraftPayloadType = getPayload();

        try {
            await saveDraftAction(payload);
            toast.success(t('messages:saveDraftSuccess'), { containerId: 'save' });
        } catch (e) {
            toast.error(t('messages:saveDraftError'), { containerId: 'save' });
        } finally {
            setSaveLoading(false);
        }
    }, [getPayload, t]);

    const handleCreateDraftClick = useCallback(() => {
        handleHelp(true);
    }, [handleHelp]);

    const handleCreateDraftSaveClick = useCallback( async() => {
        const payload: SaveDraftPayloadType = getPayload();
        await saveDraftAction(payload);
        handleHelp(false);
        setModalOpen(true);
    }, [getPayload, handleHelp]);

    const handleCreateDraftProceedClick = useCallback( async() => {
        handleHelp(false);
        setModalOpen(true);
    }, [handleHelp]);

    const handleModalCreateClick = useMemo(() => async(id: string) => {
        const payload = { location: id };
        await createDraft.onCreateDraftButtonClick(payload);
        setModalOpen(false);
    }, [createDraft]);

    const HelpControlComponent = useMemo(() => () => {
        const actions = [
            {
                onAction: handleCreateDraftSaveClick,
                title: t('header:alertCreateActionSave'),
            },
            {
                onAction: handleCreateDraftProceedClick,
                title: t('header:alertCreateActionProceed'),
            },
            {
                onAction: handleHelpClose,
                title: t('header:alertCreateActionCancel'),
            },
        ];

        return (
            <CustomHelpControl
                buttons={actions}
            />
        );
    }, [t, handleCreateDraftSaveClick, handleCreateDraftProceedClick, handleHelpClose]);

    return (
        <header className={classes.root}>
            <div className={commonClasses.container}>
                <h1 className={classes.logo}>
                    <LogoSvg/>
                    <span className={classes.title}>
                        {t('header:appTitle')}
                    </span>
                </h1>
                <div className={classes.control}>
                    {!readOnly &&
                        <>
                            <button
                                className={classes.user}
                                onClick={() => {
                                    setMyAccountOpened(true);
                                    dispatch(setDrawerOpen(true));
                                }}
                            >
                                <UserSvg/>
                                {fullName}
                            </button>
                            <div className={classes.buttons}>
                                <Tooltip
                                    text={t('my-account:tooltipLogout')}
                                >
                                    <Button
                                        className={clsx(classes.button, classes.icon, classes.logout)}
                                        theme={CONST.THEME_TRANSPARENT}
                                        onClick={handleLogout}
                                        // isLoading={createDraft.createLoading}
                                        isDisabled={readOnly}
                                    >
                                        <LogoutSvg/>
                                    </Button>
                                </Tooltip>
                                <>
                                    <Button
                                        id={'save'}
                                        className={classes.button}
                                        theme={CONST.THEME_PRIMARY}
                                        onClick={handleSaveDraftButtonClick}
                                        isLoading={saveLoading}
                                        isDisabled={readOnly}
                                    >
                                        {t('header:save')}
                                    </Button>
                                    <CartAdd
                                        buttonClassName={clsx(classes.button, classes.desktop)}
                                        buttonText={t('header:add')}
                                        readOnly={readOnly}
                                    />
                                </>
                                <Tooltip
                                    className={classes.tooltip}
                                    text={t('header:new')}
                                >
                                    <Button
                                        id={'create'}
                                        className={clsx(classes.button, classes.icon)}
                                        theme={CONST.THEME_PRIMARY}
                                        // onClick={createDraft.onCreateDraftButtonClick}
                                        onClick={handleCreateDraftClick}
                                        isLoading={createDraft.createLoading}
                                        isDisabled={readOnly}
                                    >
                                        <FilePlusSvg/>
                                    </Button>
                                </Tooltip>
                            </div>
                        </>
                    }
                </div>
            </div>
            {isDrawerOpenedSelector && isMyAccountOpened && createPortal(
                <MyAccount
                    currentDraftId={currentDraftId}
                    fullName={fullName}
                    email={email}
                    phone={phone}
                />,
                document?.getElementById('drawer') as Element,
            )}
            {open && createPortal(
                <Help
                    title={t('header:alertCreateTitle')}
                    description={t('header:alertCreateDescription')}
                    onClose={handleHelpClose}
                    customControlComponent={<HelpControlComponent />}
                />,
                document?.getElementById('grid-template') as Element,
            )}
            {modalOpen && createPortal(
                <Modal
                    onClick={handleModalCreateClick}
                />,
                document?.getElementById('body') as Element,
            )}
        </header>
    );
};

export default memo(Header);
