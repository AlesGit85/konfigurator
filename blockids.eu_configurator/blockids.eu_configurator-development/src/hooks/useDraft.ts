import { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useSelector } from 'react-redux';
import { toast } from 'react-toastify';
import { createNewDraftAction } from '@/action/createNewDraftAction';
import { SaveDraftPayloadType } from '@/app/api/_lib/endpoints';
import { configuratorGridTemplateSelector, configuratorSelector } from '@/redux/slices/configuratorSlice';

export const useDraft = () => {
    const { t } = useTranslation();

    const configurator = useSelector(configuratorSelector);
    const configuratorGridTemplate = useSelector(configuratorGridTemplateSelector);

    const [createLoading, setCreateLoading] = useState<boolean>(false);

    const handleCreateDraftButtonClick = useCallback(async(payload) => {
        setCreateLoading(true);
        try {
            await createNewDraftAction(payload);
            toast.success(t('messages:createNewDraftSuccess'), { containerId: 'create' });
        } catch (e: unknown) {
            toast.error(t('messages:createNewDraftError'), { containerId: 'create' });
        } finally {
            setCreateLoading(false);
        }
    }, [t]);

    const getPayload = useCallback((): SaveDraftPayloadType => {
        const preparedWorkspace: {[key: string]: string,} = {};

        for (const cell of Object.keys(configuratorGridTemplate)) {
            const gridItem = configuratorGridTemplate[cell];
            preparedWorkspace[cell] = gridItem?.desk?.id ? { id: gridItem.desk.id, rotation: gridItem.rotation } : '';
        }

        return {
            orientation: configurator.settings.direction,
            calculatedWidth: configurator.settings.standard.axisX,
            calculatedHeight: configurator.settings.standard.axisY,
            customWidth: configurator.settings.individual.axisX,
            customHeight: configurator.settings.individual.axisY,
            grip: configurator.accessories.hold?.id || null,
            mattress: configurator.accessories.mattress?.id || null,
            mattressQuantity: configurator.draftControl.mattress.count,
            gripQuantity: configurator.draftControl.hold.count,
            workspace: preparedWorkspace,
        };
    }, [configurator.settings, configurator.accessories, configurator.draftControl.mattress, configurator.draftControl.hold, configuratorGridTemplate]);

    return {
        createDraft: {
            onCreateDraftButtonClick: handleCreateDraftButtonClick,
            createLoading,
            setCreateLoading,
        },
        getPayload,
    };
};
