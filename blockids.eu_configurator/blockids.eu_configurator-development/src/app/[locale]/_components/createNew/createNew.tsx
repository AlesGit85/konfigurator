'use client';

import { memo, useMemo } from 'react';
import Modal from '@/components/modal/modal';
import { useDraft } from '@/hooks/useDraft';
import classes from './createNew.module.scss';

export interface INotFoundProps {

}

const CreateNew = (
    {

    }: INotFoundProps,
) => {
    const { createDraft } = useDraft();

    const handleModalCreateClick = useMemo(() => async(id: string) => {
        const payload = { location: id };
        await createDraft.onCreateDraftButtonClick(payload);
    }, [createDraft]);

    return (
        <div className={classes.root}>
            <div className={classes.container}>
                <div className={classes.brand}>
                    <Modal onClick={handleModalCreateClick} />
                </div>
            </div>
        </div>
    );
};

export default memo(CreateNew);
