import clsx from 'clsx';
import { memo } from 'react';
import { useTranslation } from 'react-i18next';
import { useSelector } from 'react-redux';
import * as CONST from '@/lib/constants';
import {
    configuratorCustomerTypeSelector,
    configuratorDraftControlSelector,
    configuratorSettingsSelector,
} from '@/redux/slices/configuratorSlice';
import classes from './draftList.module.scss';

const sumTotalPrice = (...args: number[]): number => args.reduce((accumulator, currentValue) => accumulator + currentValue);

export interface IDraftListProps {
    className?: string,
    locationType?: string,
}

const DraftList = (
    {
        className,
        locationType
    }: IDraftListProps,
) => {
    const { t } = useTranslation();

    const configuratorDraftControl = useSelector(configuratorDraftControlSelector);
    const configuratorCustomerType = useSelector(configuratorCustomerTypeSelector);
    const configuratorSettings = useSelector(configuratorSettingsSelector);

    const isCustomerTypePublic = configuratorCustomerType === CONST.CUSTOMER_TYPE_PUBLIC;

    const configuratorDraftControlRectangleCount = configuratorDraftControl.rectangle.count;
    const configuratorDraftControlTriangleCount = configuratorDraftControl.triangle.count;
    const configuratorDraftControlBlackboardCount = configuratorDraftControl.blackboard.count;
    const configuratorDraftControlHoldCount = configuratorDraftControl.hold.count;
    const configuratorDraftControlMattressCount = configuratorDraftControl.mattress.count;
    const configuratorDraftControlExtraDesignCount = configuratorDraftControl.extraDesign.count;
    const configuratorDraftControlExtraSizeCount = configuratorDraftControl.extraSize.count;

    const configuratorDraftControlRectangleTotalPrice = configuratorDraftControl.rectangle.totalPrice;
    const configuratorDraftControlTriangleTotalPrice = configuratorDraftControl.triangle.totalPrice;
    const configuratorDraftControlBlackboardTotalPrice = configuratorDraftControl.blackboard.totalPrice;
    const configuratorDraftControlHoldTotalPrice = configuratorDraftControl.hold.totalPrice;
    const configuratorDraftControlMattressTotalPrice = configuratorDraftControl.mattress.totalPrice;

    const basePriceWithoutMattress: number = sumTotalPrice(
        configuratorDraftControlRectangleTotalPrice,
        configuratorDraftControlTriangleTotalPrice,
        configuratorDraftControlBlackboardTotalPrice,
        configuratorDraftControlHoldTotalPrice,
    );

    const basePrice: number = sumTotalPrice(
        basePriceWithoutMattress,
        configuratorDraftControlMattressTotalPrice,
    );

    const extraDesignPrice: number = sumTotalPrice(
        basePriceWithoutMattress,
    ) * 0.1;

    const extraSizePrice: number = sumTotalPrice(
        basePriceWithoutMattress,
    ) * 0.1;

    const totalPrice: number = sumTotalPrice(
        basePrice,
        extraDesignPrice,
        configuratorDraftControlExtraSizeCount ? extraSizePrice : 0,
    );

    const axisX: number = configuratorDraftControlExtraSizeCount ? configuratorSettings.realtime.individual.axisX : configuratorSettings.standard.axisX;
    const axisY: number = configuratorDraftControlExtraSizeCount ? configuratorSettings.realtime.individual.axisY : configuratorSettings.standard.axisY;

    return (
        <div className={clsx(classes.root, className)}>
            <div className={classes.title}>{t('draft-control:myWall')}</div>
            <div className={classes.calculation}>
                <div className={classes.items}>
                    <div
                        className={clsx(classes.item, classes.offset, !configuratorDraftControlRectangleCount && classes.empty)}
                    >
                        <span>{configuratorDraftControlRectangleCount} x {t('draft-control:deskRectangle')}</span>
                        <span>{t('common:currency', { val: configuratorDraftControlRectangleTotalPrice })}</span>
                    </div>
                    <div
                        className={clsx(classes.item, classes.offset, !configuratorDraftControlTriangleCount && classes.empty)}
                    >
                        <span>{configuratorDraftControlTriangleCount} x {t('draft-control:deskTriangle')}</span>
                        <span>{t('common:currency', { val: configuratorDraftControlTriangleTotalPrice })}</span>
                    </div>
                    <div
                        className={clsx(classes.item, classes.offset, !configuratorDraftControlBlackboardCount && classes.empty)}
                    >
                        <span>{configuratorDraftControlBlackboardCount} x {t('draft-control:deskBlackboard')}</span>
                        <span>{t('common:currency', { val: configuratorDraftControlBlackboardTotalPrice })}</span>
                    </div>
                    <div
                        className={clsx(classes.item, classes.offset, !configuratorDraftControlHoldCount && classes.empty)}
                    >
                        <span>{configuratorDraftControlHoldCount} x {t('draft-control:holds')}</span>
                        <span>{t('common:currency', { val: configuratorDraftControlHoldTotalPrice })}</span>
                    </div>
                    {locationType !== CONST.LOCATION_TYPE_OUTDOOR && (<div
                        className={clsx(classes.item, !isCustomerTypePublic && classes.offset, !configuratorDraftControlMattressCount && classes.empty)}
                    >
                        <span>{!isCustomerTypePublic && `${configuratorDraftControlMattressCount} x`} {t('draft-control:mattress')}</span>
                        <span>{t('common:currency', { val: configuratorDraftControlMattressTotalPrice })}</span>
                    </div>)}
                    <div className={clsx(classes.item, !configuratorDraftControlExtraDesignCount && classes.empty)}>
                        <span>{t('draft-control:extraDesign')}</span>
                        <span>{t('common:currency', { val: extraDesignPrice })}</span>
                    </div>
                    <div className={clsx(!configuratorDraftControlExtraSizeCount && classes.empty)}>
                        <div className={clsx(classes.item)}>
                            <span>{t('draft-control:extraSize')}</span>
                            <span>{t('common:currency', { val: configuratorDraftControlExtraSizeCount ? extraSizePrice : 0 })}</span>
                        </div>
                        <div className={clsx(classes.sizeInfo)}>
                            <span>- {t('draft-control:gridSize', { axisX: axisX, axisY: axisY })}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div className={classes.item}>
                <span>{t('draft-control:priceTotal')}</span>
                <span>{t('common:currency', { val: totalPrice })}</span>
            </div>
        </div>
    );
};

export default memo(DraftList);
