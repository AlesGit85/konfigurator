'use client';

import clsx from 'clsx';
import { BaseSyntheticEvent, memo, useCallback, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useDispatch } from 'react-redux';
import { changeTitleAction } from '@/action/changeTitleAction';
import { getDraftListAction } from '@/action/getDraftListAction';
import Input from '@/components/input/input';
import { setDraftList } from '@/redux/slices/configuratorSlice';
import { AppDispatch } from '@/redux/store';
import classes from './draftTitle.module.scss';

export interface IDraftTitleProps {
    className?: string
    initialData?: string
    readOnly: boolean
}

const DraftTitle = (
    {
        className,
        initialData,
        readOnly,
    }: IDraftTitleProps,
) => {
    const { t } = useTranslation();

    const dispatch: AppDispatch = useDispatch();

    const [title, setTitle] = useState(initialData);
    const [isModify, setModify] = useState(false);

    const handleTitleChange = useCallback((event: BaseSyntheticEvent) => setTitle(event.target.value), []);

    const handleTitleModify = useCallback(() => setModify(true), []);

    const handleTitleCancel = useCallback(() => setModify(false), []);

    const handleTitleSave = useCallback(async() => {
        try {
            await changeTitleAction(title);
            const updatedDraftList = await getDraftListAction();
            dispatch(setDraftList(updatedDraftList));
            setModify(false);
        } catch (e) {
            console.error('Not allowed: ' + e);
        }
    }, [dispatch, title]);

    const defaultUntitled = useMemo(() => t('draft-control:untitledDefault'), [t]);

    return (
        <div className={clsx(classes.root, className)}>
            <div className={classes.title}>{t('draft-control:titleLabel')}</div>
            <div className={classes.content}>
                {isModify ? (
                    <>
                        <Input
                            id={'title-change'}
                            className={classes.inputContainer}
                            inputClassName={classes.input}
                            onChange={handleTitleChange}
                            placeholder={defaultUntitled}
                            type={'text'}
                            value={title}
                        />
                        <div className={classes.action}>
                            <button
                                className={classes.button}
                                onClick={handleTitleCancel}
                            >
                                {t('draft-control:titleCancelButton')}
                            </button>
                            <button
                                className={classes.button}
                                onClick={handleTitleSave}
                            >
                                {t('draft-control:titleSaveButton')}
                            </button>
                        </div>
                    </>
                ) : (
                    <>
                        <div className={classes.name}>{title || defaultUntitled}</div>
                        {!readOnly &&
                            <div className={classes.action}>
                                <button
                                    className={classes.button}
                                    onClick={handleTitleModify}
                                >
                                    {t('draft-control:titleChangeButton')}
                                </button>
                            </div>
                        }
                    </>
                )}
            </div>
        </div>
    );
};

export default memo(DraftTitle);
