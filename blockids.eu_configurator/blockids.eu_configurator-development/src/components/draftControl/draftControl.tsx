'use client';

import clsx from 'clsx';
import { memo } from 'react';
import { useTranslation } from 'react-i18next';
import CartAdd from '@/components/cartAdd/cartAdd';
import DraftList from '@/components/draftControl/_components/draftList/draftList';
import DraftTitle from '@/components/draftControl/_components/draftTitle/draftTitle';
import classes from './draftControl.module.scss';
import { useSelector } from 'react-redux';
import { configuratorDraftControlSelector } from '@/redux/slices/configuratorSlice';

export interface IDraftControlProps {
    className?: string
    initialData?: {
        title: string | null,
    }
    readOnly: boolean
    locationType?: string
}

const DraftControl = (
    {
        className,
        initialData,
        readOnly,
        locationType
    }: IDraftControlProps,
) => {
    const { t } = useTranslation();

    const draftControl = useSelector(configuratorDraftControlSelector);

    const readOnlyAddToCart = draftControl.rectangle.count === 0 && draftControl.triangle.count === 0 && draftControl.blackboard.count === 0;

    return (
        <div className={clsx(classes.root, className)}>
            <DraftTitle
                className={classes.title}
                initialData={initialData?.title}
                readOnly={readOnly}
            />
            <DraftList
                locationType={locationType}
                className={classes.list}
            />
            {!readOnly &&
                <div className={classes.action}>
                    <CartAdd
                        locationType={locationType}
                        buttonClassName={classes.button}
                        buttonText={t('draft-control:cartAdd')}
                        readOnly={readOnly || readOnlyAddToCart}
                    />
                </div>
            }
        </div>
    );
};

export default memo(DraftControl);
