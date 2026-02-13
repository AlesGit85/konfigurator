import ConfiguratorContainer from '@/app/[locale]/_components/configuratorContainer/configuratorContainer';
import NotFound from '@/app/[locale]/_components/notFound/notFound';
import {
    getCustomerMe,
    getDeskList,
    getDraftDetail,
    getFaqList,
    getHoldList,
    getMattressList,
} from '@/app/api/_lib/endpoints';
import initTranslations from '@/app/i18n';
import mattress from '@/components/dnd/mattress/mattress';
import Drawer from '@/components/drawer/drawer';
import Header from '@/components/header/header';
import * as CONST from '@/lib/constants';
import { getCustomerType } from '@/lib/utils';
import TranslationsProvider from '@/providers/TranslationsProvider';
import CreateNew from '@/app/[locale]/_components/createNew/createNew';

const FAMILY = CONST.CUSTOMER_TYPE_FAMILY;

const i18nNamespaces = ['common', 'settings', 'header', 'draft-control', 'grid', 'help', 'toolbar', 'messages', 'my-account', 'manual', 'inspiration', 'modal'];

export interface IPageContainerProps {
    locale: string
    token: string
    accessHash: string
    isDraftReadOnly?: boolean
}

export default async function PageContainer(
    {
        locale,
        token,
        accessHash,
        isDraftReadOnly,
    }: IPageContainerProps,
) {
    const { resources } = await initTranslations(locale, i18nNamespaces);

    const user = await getCustomerMe(token);
    let draftDetail;
    try {
        draftDetail = await getDraftDetail(locale, token, accessHash);
    } catch (e) {}
    const deskList = await getDeskList(locale);
    const holdList = await getHoldList(locale);
    const mattressList = await getMattressList(locale);
    const faqList = await getFaqList(locale);
    const draftList = user.data.plans || [];
    const locationType = draftDetail?.data?.location;

    const draftDetailWorkspace = draftDetail?.data?.workspace;
    const mattressFilteredList = mattressList?.data?.filter(m => getCustomerType(user?.data?.segment?.id) === FAMILY ? m.personal : !m.personal);

    const isReadOnly: boolean = !!isDraftReadOnly || draftDetail?.data?.status === CONST.DRAFT_STATUS_ORDERED;

    return (
        <TranslationsProvider
            locale={locale}
            namespaces={i18nNamespaces}
            resources={resources}
        >
            <Header
                currentDraftId={draftDetail?.data?.accessHash}
                fullName={user?.data?.givenName + ' ' + user?.data?.familyName}
                email={user?.data?.email}
                phone={user?.data?.phone}
                readOnly={isReadOnly}
            />
            {!draftDetail?.data?.accessHash ?
                <CreateNew /> :
                (
                    <>
                        <ConfiguratorContainer
                            customerType={user?.data?.segment?.id}
                            locationType={draftDetail?.data.location}
                            initialData={{
                                hash: draftDetail?.data.accessHash,
                                title: draftDetail?.data?.title,
                                direction: draftDetail?.data?.orientation,
                                deskList: deskList?.data?.filter((desk: { location: string, }) => desk.location === locationType),
                                draftList: draftList,
                                grid: Array.isArray(draftDetailWorkspace) ? {} : draftDetailWorkspace,
                                standard: {
                                    axisX: draftDetail?.data?.calculatedWidth,
                                    axisY: draftDetail?.data?.calculatedHeight,
                                },
                                individual: {
                                    axisX: draftDetail?.data?.customWidth,
                                    axisY: draftDetail?.data?.customHeight,
                                },
                                hold: {
                                    selected: draftDetail?.data?.grip,
                                    list: holdList.data,
                                },
                                mattress: {
                                    selected: draftDetail?.data?.mattress,
                                    list: mattressFilteredList,
                                },
                                faq: faqList.data,
                            }}
                            readOnly={isReadOnly}
                        />
                    </>
                )
            }
            <Drawer id={'drawer'} />
        </TranslationsProvider>
    );
}
