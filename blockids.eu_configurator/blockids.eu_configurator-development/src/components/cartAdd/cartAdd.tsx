import { useRouter } from 'next/navigation';
import { memo, useCallback, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { useSelector } from 'react-redux';
import { toast } from 'react-toastify';
import { confirmDraftAction } from '@/action/confirmDraftAction';
import { SaveDraftPayloadType } from '@/app/api/_lib/endpoints';
import Button from '@/components/button/button';
import HelpControl from '@/components/help/control/control';
import Help from '@/components/help/help';
import { useDraft } from '@/hooks/useDraft';
import * as CONST from '@/lib/constants';
import { configuratorHoldsSelector, configuratorMattressSelector } from '@/redux/slices/configuratorSlice';

const WEBSITE_REDIRECT_PATH = process.env.NEXT_PUBLIC_URL_REDIRECT_PATH || '';

export interface ICartAddProps {
    buttonClassName: string
    buttonText: string
    readOnly?: boolean
    locationType?: string
}

const CartAdd = (
    {
        buttonClassName,
        buttonText,
        readOnly,
        locationType,
    }: ICartAddProps,
) => {
    const { t } = useTranslation();

    const router = useRouter();

    const [cartAddLoading, setCartAddLoading] = useState<boolean>(false);
    const [open, setOpen] = useState<boolean>(false);

    const { getPayload } = useDraft();

    const configuratorHoldSelected = useSelector(configuratorHoldsSelector);
    const configuratorMattressSelected = useSelector(configuratorMattressSelector);

    const properAlertDescription: string = useMemo((): string => {
        if (locationType !== CONST.LOCATION_TYPE_OUTDOOR && !configuratorHoldSelected?.id && !configuratorMattressSelected?.id) return t('draft-control:alertDescriptionBoth');
        if (!configuratorHoldSelected?.id) return t('draft-control:alertDescriptionGrips');
        if (locationType !== CONST.LOCATION_TYPE_OUTDOOR && !configuratorMattressSelected?.id) return t('draft-control:alertDescriptionMattress');
        return '';
    }, [configuratorHoldSelected, configuratorMattressSelected]);

    const handleHelp = useCallback((isOpen: boolean): void => setOpen(isOpen), []);

    const handleCartAdd = useCallback(async(): Promise<void> => {
        setCartAddLoading(true);
        const payload: SaveDraftPayloadType = getPayload();

        try {
            await confirmDraftAction(payload);
            toast.success(t('messages:confirmDraftSuccess'));
        } catch (e) {
            toast.error(t('messages:confirmDraftError'));
        } finally {
            setCartAddLoading(false);
            handleHelp(false);
        }
    }, [getPayload, handleHelp, t, router]);

    const handleCartAddClick = useCallback(async(): Promise<void> => {
        const payload: SaveDraftPayloadType = getPayload();

        if ((locationType !== CONST.LOCATION_TYPE_OUTDOOR && !payload.mattress) || !payload.grip) {
            handleHelp(true);
        } else {
            await handleCartAdd();
        }
    }, [handleHelp, getPayload, handleCartAdd]);

    const handleHelpClose = useCallback(() => handleHelp(false), [handleHelp]);

    const HelpControlComponent = useMemo(() => () => (
        <HelpControl
            onProceed={handleCartAdd}
            onClose={handleHelpClose}
        />
    ), [handleCartAdd, handleHelpClose]);

    return (
        <>
            <Button
                className={buttonClassName}
                theme={CONST.THEME_PRIMARY}
                onClick={handleCartAddClick}
                isLoading={cartAddLoading}
                isDisabled={readOnly}
            >
                {buttonText}
            </Button>
            {open && createPortal(
                <Help
                    title={t('draft-control:alertTitle')}
                    description={properAlertDescription}
                    onClose={handleHelpClose}
                    customControlComponent={<HelpControlComponent />}
                />,
                document?.getElementById('grid-template') as Element,
            )}
        </>
    );
};

export default memo(CartAdd);
