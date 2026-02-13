'use client';

import CopyRegularSvg from '/public/icons/copy-regular.svg';
import PenSquareSvg from '/public/icons/pen-square.svg';
import TrashCanSvg from '/public/icons/trash-can.svg';
import { useRouter } from 'next/navigation';
import { memo, useCallback, useState } from 'react';
import Button from '@/components/button/button';
import classes from './myAccount.module.scss';
import { useTranslation } from 'react-i18next';
import { configuratorDraftListSelector, setDraftList, setDrawerOpen } from '@/redux/slices/configuratorSlice';
import { useDispatch, useSelector } from 'react-redux';
import { DraftListItemType } from '@/redux/types/configuratorTypes';
import { updateSessionAction } from '@/action/session/updateSessionAction';
import * as CONST from '@/lib/constants';
import { AppDispatch } from '@/redux/store';
import { toast } from 'react-toastify';
import Spinner from '@/components/spinner/spinner';
import clsx from 'clsx';
import { copyDraftAction } from '@/action/copyDraftAction';
import { deleteDraftAction } from '@/action/deleteDraftAction';
import Tooltip from '@/components/tooltip/tooltip';
import { getDraftListAction } from '@/action/getDraftListAction';

export interface IMyAccountProps {
    currentDraftId: string;
    fullName: string;
    email: string;
    phone: string;
}

const MyAccount = (
    {
        currentDraftId,
        fullName,
        email,
        phone,
    }: IMyAccountProps,
) => {
    const { t, i18n: { language: locale } } = useTranslation();

    const [actionIsLoading, setActionLoading] = useState<boolean>(false);

    const router = useRouter();
    const dispatch: AppDispatch = useDispatch();

    const draftListSelector = useSelector(configuratorDraftListSelector);

    const handleEditClick = useCallback(async(id: string): Promise<void> => {
        setActionLoading(true);
        try {
            await updateSessionAction(CONST.DRAFT_ID_HASH, id);
            window.location.href = `/${locale}/detail/${id}`;

            dispatch(setDrawerOpen(false));
            toast.success(t('messages:changeDraftSuccess'));
        } catch (e) {
            toast.error(t('messages:changeDraftError'));
        } finally {
            setActionLoading(false);
        }
    }, [dispatch, router]);

    const handleCopyClick = useCallback(async(accessHash: string): Promise<void> => {
        setActionLoading(true);
        try {
            await copyDraftAction(accessHash);
            dispatch(setDrawerOpen(false));
            toast.success(t('messages:copyDraftSuccess'));
        } catch (e) {
            toast.error(t('messages:copyDraftError'));
        } finally {
            setActionLoading(false);
        }
    }, [dispatch]);

    const handleDeleteClick = useCallback(async(accessHash: string): Promise<void> => {
        setActionLoading(true);
        try {
            await deleteDraftAction(accessHash);
            const updatedDraftList: DraftListItemType[] = await getDraftListAction();

            if (!updatedDraftList.length) {
                window.location.reload();
            }

            dispatch(setDraftList(updatedDraftList));
            dispatch(setDrawerOpen(false));
            toast.success(t('messages:deleteDraftSuccess'));
        } catch (e) {
            toast.error(t('messages:deleteDraftError'));
        } finally {
            setActionLoading(false);
        }
    }, [dispatch]);

    return (
        <div className={classes.root}>
            {actionIsLoading && <Spinner className={classes.loading}/>}
            <div className={classes.section}>
                <div className={classes.title}>{t('my-account:myAccountTitle')}</div>
                <div className={classes.content}>
                    <div className={classes.name}>
                        <span>{fullName}</span>
                        {false && <button className={classes.button}>{t('my-account:buttonChange')}</button>}
                    </div>
                    <div className={classes.info}>
                        <span>{email}</span>
                        <span>{phone}</span>
                    </div>
                </div>
            </div>
            <div className={classes.section}>
                <div className={classes.title}>{t('my-account:myDraftsTitle')}</div>
                <div className={classes.content}>
                    <ul className={classes.ul}>
                        {draftListSelector?.map((li: DraftListItemType) => {
                            const isDraftSelected = currentDraftId === li.accessHash;

                            return (
                                <li
                                    key={li.accessHash}
                                    className={clsx(classes.li, isDraftSelected && classes.selected)}
                                >
                                    <div className={classes.label}>
                                        <div
                                            className={classes.labelName}>{li.name || t('draft-control:untitledDefault')}</div>
                                        {/* <span className={classes.labelDate}>(12. 9. 2023)</span>*/}
                                    </div>
                                    <div className={classes.actions}>
                                        {!isDraftSelected &&
                                            <Tooltip
                                                className={classes.tooltip}
                                                text={t('my-account:tooltipEdit')}
                                            >
                                                <Button
                                                    className={classes.button}
                                                    theme={'secondary'}
                                                    onClick={() => handleEditClick(li.accessHash)}
                                                >
                                                    <PenSquareSvg/>
                                                </Button>
                                            </Tooltip>
                                        }
                                        <Tooltip
                                            className={classes.tooltip}
                                            text={t('my-account:tooltipCopy')}
                                        >
                                            <Button
                                                className={classes.button}
                                                theme={'secondary'}
                                                onClick={() => handleCopyClick(li.accessHash)}
                                            >
                                                <CopyRegularSvg/>
                                            </Button>
                                        </Tooltip>
                                        {!isDraftSelected &&
                                            <Tooltip
                                                className={classes.tooltip}
                                                text={t('my-account:tooltipDelete')}
                                            >
                                                <Button
                                                    className={classes.button}
                                                    theme={'secondary'}
                                                    onClick={() => handleDeleteClick(li.accessHash)}
                                                >
                                                    <TrashCanSvg/>
                                                </Button>
                                            </Tooltip>
                                        }
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>
        </div>
    );
};

export default memo(MyAccount);
