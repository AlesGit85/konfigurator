import * as CONST from '@/lib/constants';
import { LOCATION_TYPE_INDOOR, LOCATION_TYPE_OUTDOOR } from '@/lib/constants';

export type ConfiguratorState = {
    settings: {
        direction: string,
        standard: AxisType,
        individual: AxisType,
        realtime: {
            individual: AxisType,
        },
    },
    accessories: {
        hold: object, // TODO udelat podle API
        mattress: object, // TODO udelat podle API
    },
    location: LocationType,
    customer: CustomerType,
    grid: GridTemplateType,
    draftControl: DraftControlTypes,
    history: {
        currentPosition: number,
        totalPosition: number,
        list: [],
    },
    draftList: DraftListItemType[],
    temp: {
        drawer: {
            isOpened: boolean,
        },
        holdList: object[],
        mattressList: object[],
        gridCountMax: number,
    },
}

export type LocationType = typeof CONST.LOCATION_TYPE_INDOOR | typeof CONST.LOCATION_TYPE_OUTDOOR | undefined;
export type CustomerType = typeof CONST.CUSTOMER_TYPE_FAMILY | typeof CONST.CUSTOMER_TYPE_PUBLIC;

export type AxisType = {
    axisX: number,
    axisY: number,
}

export type GridTemplateType = {
    [key: string]: {
        [key: string]: {
            desk: {
                id: string,
                name: string,
                image: string,
                type: string,
                price: string,
                currency: string,
            },
            rotation: number,
        } | '',
    },
}

export type DraftListItemType = {
    id: number,
    name: string,
    accessHash: string,
}

export type DraftControlAllowedTypes = 'rectangle' | 'triangle' | 'mattress' | 'hold' | 'blackboard' | 'extraDesign' | 'extraSize'

type DraftControlValuesType = {
    count: number,
    totalPrice: number,
}

export type DraftControlTypes = {
    rectangle:DraftControlValuesType,
    triangle: DraftControlValuesType,
    mattress: DraftControlValuesType,
    hold: DraftControlValuesType,
    blackboard: DraftControlValuesType,
    extraDesign: DraftControlValuesType,
    extraSize: DraftControlValuesType,
}
