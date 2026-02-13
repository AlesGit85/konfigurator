import { createAsyncThunk, createSlice, current } from '@reduxjs/toolkit';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import * as CONST from '@/lib/constants';
import { GRID_CONFIG } from '@/lib/grid';
import { deepEqual, getDeskType, getRandomNumber } from '@/lib/utils';
import { RootState } from '@/redux/store';
import {
    ConfiguratorState,
    CustomerType,
    DraftControlAllowedTypes,
    DraftListItemType, LocationType,
} from '@/redux/types/configuratorTypes';

const initialState: ConfiguratorState = {
    settings: {
        direction: CONST.GRID_ALIGNMENT_VERTICAL,
        // in cm
        standard: {
            axisX: 0,
            axisY: 0,
        },
        // in cm
        individual: {
            axisX: 0,
            axisY: 0,
        },
        realtime: {
            individual: {
                axisX: 0,
                axisY: 0,
            },
        },
    },
    accessories: {
        hold: {},
        mattress: {},
    },
    location: '',
    customer: '',
    // grid template per direction
    grid: {
        horizontal: {
            A1: '',
            B1: '',
            C1: '',
            D1: '',
            E1: '',

            A2: '',
            B2: '',
            C2: '',
            D2: '',
            E2: '',

            A3: '',
            B3: '',
            C3: '',
            D3: '',
            E3: '',

            A4: '',
            B4: '',
            C4: '',
            D4: '',
            E4: '',
        },
        vertical: {
            A1: '',
            B1: '',
            C1: '',
            D1: '',
            E1: '',
            F1: '',
            G1: '',
            H1: '',

            A2: '',
            B2: '',
            C2: '',
            D2: '',
            E2: '',
            F2: '',
            G2: '',
            H2: '',

            A3: '',
            B3: '',
            C3: '',
            D3: '',
            E3: '',
            F3: '',
            G3: '',
            H3: '',
        },
    },
    draftControl: {
        rectangle: {
            count: 0,
            totalPrice: 0,
        },
        triangle: {
            count: 0,
            totalPrice: 0,
        },
        mattress: {
            count: 0,
            totalPrice: 0,
        },
        hold: {
            count: 0,
            totalPrice: 0,
        },
        blackboard: {
            count: 0,
            totalPrice: 0,
        },
        extraDesign: {
            count: 1,
            totalPrice: 0,
        },
        extraSize: {
            count: 0,
            totalPrice: 0,
        },
    },
    draftList: [],
    history: {
        currentPosition: 0,
        totalPosition: 0,
        list: [],
    },
    temp: {
        drawer: {
            isOpened: false,
        },
        holdList: [],
        mattressList: [],
    },
};

// Create slice
export const configuratorSlice = createSlice({
    name: 'configurator',
    initialState,
    reducers: {
        setDraftDirection: (state: ConfiguratorState, action: { payload: GridDirectionType,}) => {
            state.settings.direction = action.payload;
        },
        setCustomerType: (state: ConfiguratorState, action: { payload: CustomerType,}) => {
            state.customer = action.payload;
        },
        setLocationType: (state: ConfiguratorState, action: { payload: LocationType,}) => {
            state.location = action.payload;
        },
        setIndividualAxis: (state: ConfiguratorState, action: { payload: { axisX?: number, axisY?: number,},}) => {
            if (action.payload.axisX) {
                state.settings.individual.axisX = action.payload.axisX;
            }
            if (action.payload.axisY) {
                state.settings.individual.axisY = action.payload.axisY;
            }
        },
        setRealtimeIndividualAxis: (state: ConfiguratorState, action: { payload: { axisX?: number, axisY?: number,},}) => {
            const currentState: ConfiguratorState = current(state);
            let isExtraSizeX: boolean = false;
            let isExtraSizeY: boolean = false;

            if (action.payload.axisX) {
                state.settings.realtime.individual.axisX = action.payload.axisX;
                isExtraSizeX = action.payload.axisX < currentState.settings.standard.axisX || currentState.settings.realtime.individual.axisY < currentState.settings.standard.axisY;
            }
            if (action.payload.axisY) {
                state.settings.realtime.individual.axisY = action.payload.axisY;
                isExtraSizeY = action.payload.axisY < currentState.settings.standard.axisY || currentState.settings.realtime.individual.axisX < currentState.settings.standard.axisX;
            }

            state.draftControl.extraSize.count = isExtraSizeX || isExtraSizeY ? 1 : 0;
        },
        setStandardAxis: (state: ConfiguratorState) => {
            const currentState: ConfiguratorState = current(state);
            const direction: string = currentState.settings.direction;
            const grid = currentState.grid[direction];
            const gridConfigDirection = GRID_CONFIG[direction];
            const maxAxisY = GRID_CONFIG[direction].axisY;

            let countX = 0;
            let countY = 0;

            const lowMax = maxAxisY.reduce((acc, _, index) => {
                acc[index + 1] = {
                    low: 0,
                    max: 0,
                };
                return acc;
            }, {});

            const alreadyIterated: Set<string> = new Set();

            // pro kazdy radek v maxAxisYLenght - proiterovat radek a ulozit nejvyssi hodnotu vlozenych desku
            for (const row of maxAxisY) {
                let i = 0;

                for (const [key, value] of Object.entries(grid)) {
                    const keyRow = key.substring(1);
                    if (keyRow == row) i++;

                    if (key.includes(row.toString()) && value) {
                        countX += 1;
                        lowMax[row].low = lowMax[row].low || i;
                        lowMax[row].max = i;
                    }

                    if (!alreadyIterated.has(keyRow)) {
                        if (value) {
                            countY += 1;
                            alreadyIterated.add(keyRow);
                        }
                    }
                }
            }

            const lows = Object.values(lowMax).map(item => item.low).filter(v => v > 0);
            const maxs = Object.values(lowMax).map(item => item.max);
            const minValue = !!lows.length ? Math.max(Math.min(...lows) - 1, 0) : 0;
            const maxValue = Math.max(...maxs);

            // counts proper count of used desk
            const res = maxValue - (minValue ? minValue - 1 : minValue);

            const newStandardAxisX: number = maxValue * gridConfigDirection.cmBaseX;
            const newStandardAxisY: number = countY * gridConfigDirection.cmBaseY;

            if (currentState.settings.individual.axisX < newStandardAxisX) {
                //state.settings.individual.axisX = (countX * gridConfigDirection.cmBaseX) + gridConfigDirection.cmMinCutX;
                //state.settings.realtime.individual.axisX = (countX * gridConfigDirection.cmBaseX) + gridConfigDirection.cmMinCutX;
            }

            if (currentState.settings.individual.axisY < newStandardAxisY) {
                //state.settings.individual.axisY = (countY * gridConfigDirection.cmBaseY) + gridConfigDirection.cmMinCutY;
                //state.settings.realtime.individual.axisY = (countY * gridConfigDirection.cmBaseY) + gridConfigDirection.cmMinCutY;
            }

            state.settings.standard.axisX = newStandardAxisX;
            state.settings.standard.axisY = newStandardAxisY;
            state.temp.gridCountMax = maxValue;
        },
        setInitialAccessory: (state: ConfiguratorState, action: {
            payload: {
                hold?: object,
                mattress?: object,
            },},
        ) => {
            state.accessories.hold = {};
            state.accessories.mattress = {};

            state.draftControl.hold.count = 0;
            state.draftControl.hold.totalPrice = 0;

            state.draftControl.mattress.count = 0;
            state.draftControl.mattress.totalPrice = 0;

            if (action.payload.hold?.id) {
                state.accessories.hold = action.payload.hold;
                state.draftControl.hold.count = action.payload.hold?.price ? 1 : 0;
                state.draftControl.hold.totalPrice = action.payload.hold?.price || 0;
            }

            if (action.payload.mattress?.id) {
                state.accessories.mattress = action.payload.mattress;
                state.draftControl.mattress.count = action.payload.mattress?.price ? 1 : 0;
                state.draftControl.mattress.totalPrice = action.payload.mattress?.price || 0;
            }
        },
        setAccessory: (state: ConfiguratorState, action: {
            payload: {
                hold?: string,
                mattress?: string,
            },},
        ) => {
            if (action.payload.hold) {
                state.accessories.hold = action.payload.hold;
            }
            if (action.payload.mattress) {
                state.accessories.mattress = action.payload.mattress;
            }
        },
        setInitialGrid: (state: ConfiguratorState, action: {
            payload: {
                targets: object,
                direction: string,
            },
        }) => {
            // init values
            state.draftControl.rectangle.count = 0;
            state.draftControl.rectangle.totalPrice = 0;
            state.draftControl.triangle.count = 0;
            state.draftControl.triangle.totalPrice = 0;
            state.draftControl.blackboard.count = 0;
            state.draftControl.blackboard.totalPrice = 0;

            state.grid[action.payload.direction] = { ...initialState.grid[action.payload.direction] };

            if (!Object?.entries(action.payload.targets).length) {
                state.grid[action.payload.direction] = initialState.grid[action.payload.direction];
            } else {
                for (const [key, value] of Object?.entries(action.payload.targets)) {
                    if (value?.desk?.id) {
                        const deskType = getDeskType(value.desk.type);

                        state.grid[action.payload.direction][key] = {
                            desk: {
                                id: value.desk.id,
                                name: value.desk.title,
                                image: value.desk.image,
                                type: value.desk.type,
                                price: value.desk.price,
                                currency: value.desk.currency,
                            },
                            rotation: value.rotation,
                        };
                        state.draftControl[deskType].count += 1;
                        state.draftControl[deskType].totalPrice += value.desk.price;
                    }
                }
            }
        },
        setGrid: (state: ConfiguratorState, action: {
            payload: {
                source?: {
                    cellId: string,
                    value: string,
                },
                target?: {
                    cellId: string | number,
                    name: string,
                    value: string,
                    image: string,
                    type: DraftControlAllowedTypes,
                    price: string,
                    currency: string,
                    overlay: {
                        id: string,
                        type: string,
                        orientation: string,
                        rotation: number,
                        inputs: boolean,
                        image: string,
                    },
                    rotation: number,
                },
            },
        }) => {
            const currentState: ConfiguratorState = current(state);
            if (action.payload.source?.cellId) {
                state.grid[currentState.settings.direction][action.payload.source?.cellId] = '';
            }

            if (action.payload.target?.cellId) {
                state.grid[currentState.settings.direction][action.payload.target?.cellId] = {
                    desk: {
                        id: action.payload.target?.value,
                        name: action.payload.target?.name,
                        image: action.payload.target?.image,
                        type: action.payload.target?.type,
                        price: action.payload.target?.price,
                        currency: action.payload.target?.currency,
                        overlay: action.payload.target?.overlay,
                    },
                    rotation: action.payload.target.rotation || 0,
                };
            }
        },
        updateOverlay: (state: ConfiguratorState, action: {
            payload: {
                isRemove: boolean,
                isOverlayChange: boolean,
            },
        }) => {
            const currentState: ConfiguratorState = current(state);

            const direction = currentState.settings.direction;
            const grid = currentState.grid[direction];
            const holdsOverlaysStandardsArr = currentState.accessories.hold?.overlays?.filter(overlay => !overlay?.inputs && overlay.orientation === direction) || [];
            const holdsOverlaysInputsArr = currentState.accessories.hold?.overlays?.filter(overlay => overlay?.inputs && overlay.orientation === direction) || [];

            let updated = {};
            const shouldRemove = action.payload.isRemove;

            for (const [key, value] of Object.entries(grid)) {
                const desk = value?.desk;
                const rotation = value?.rotation;

                // initial object value, that is rewritten if condition is met, otherwise cell value is not changed
                updated = {
                    ...updated,
                    [key]: value,
                };

                // table alias blackboard should not have grips
                if (desk?.id && getDeskType(desk?.type).includes('blackboard')) {
                    continue;
                }

                if (shouldRemove) {
                    if (desk?.id && desk?.overlay) {
                        updated = {
                            ...updated,
                            [key]: {
                                desk: {
                                    ...desk,
                                },
                                rotation: rotation,
                            },
                        };

                        delete updated[key].desk.overlay;
                    }
                } else {
                    if (desk?.id) {
                        const isFirstRow = key.includes('1');
                        const holdsOverlaysInputsFilteredArr = holdsOverlaysInputsArr.filter(hold => (hold.rotation === rotation || hold.type === 'rectangle') && hold.type === desk.type);
                        const holdsOverlaysStandardsFilteredArr = holdsOverlaysStandardsArr.filter(hold => (hold.rotation === rotation || hold.type === 'rectangle') && hold.type === desk.type);
                        const holdsSourceArr = isFirstRow ? (!!holdsOverlaysInputsFilteredArr.length ? holdsOverlaysInputsFilteredArr : holdsOverlaysStandardsFilteredArr) : holdsOverlaysStandardsFilteredArr;
                        const overlay: number = getRandomNumber(holdsSourceArr?.length);
                        const itemOverlay = holdsSourceArr[overlay - 1];

                        if (!desk?.overlay) {
                            updated = {
                                ...updated,
                                [key]: {
                                    desk: {
                                        ...desk,
                                        overlay: itemOverlay,
                                    },
                                    rotation: rotation,
                                },
                            };
                        } else {
                            if (action.payload.isOverlayChange) {
                                updated = {
                                    ...updated,
                                    [key]: {
                                        desk: {
                                            ...desk,
                                            overlay: itemOverlay,
                                        },
                                        rotation: rotation,
                                    },
                                };
                            }
                        }
                    }
                }
            }

            state.grid[currentState.settings.direction] = {
                ...updated,
            };
        },
        updateRotation: (state: ConfiguratorState, action: {
            payload: {
                cellId: string,
            },
        }) => {
            const currentState: ConfiguratorState = current(state);

            const currentStateCellObj = currentState.grid[currentState.settings.direction][action.payload.cellId];

            state.grid[currentState.settings.direction][action.payload.cellId] = {
                desk: {
                    ...currentStateCellObj.desk,
                },
                rotation: !!currentStateCellObj.rotation ? 0 : 180,
            };
        },
        recalculateDraft: (state: ConfiguratorState) => {
            const currentState: ConfiguratorState = current(state);

            state.draftControl = {
                ...state.draftControl,
                rectangle: initialState.draftControl.rectangle,
                triangle: initialState.draftControl.triangle,
                blackboard: initialState.draftControl.blackboard,
            };

            Object.values(currentState.grid[currentState.settings.direction]).forEach((item) => {
                if (item?.desk?.id) {
                    const deskType = getDeskType(item.desk.type);

                    state.draftControl[deskType] = {
                        ...state.draftControl[deskType],
                        count: state.draftControl[deskType].count + 1,
                        totalPrice: state.draftControl[deskType].totalPrice + item.desk?.price,
                    };
                }
            });
        },
        recalculateDraftByGridAction: (state: ConfiguratorState, action: { payload: {
            type: DraftControlAllowedTypes,
        },}) => {
            const currentState: ConfiguratorState = current(state);

            state.draftControl[action.payload.type].count = 0;
            state.draftControl[action.payload.type].totalPrice = 0;

            Object.values(currentState.grid[currentState.settings.direction]).forEach((item) => {
                if (item?.desk?.id) {
                    if (getDeskType(item.desk.type).includes(action.payload.type)) {
                        state.draftControl[action.payload.type].count += 1;
                        state.draftControl[action.payload.type].totalPrice += item.desk?.price;
                    }
                }
            });
        },
        recalculateDraftByAccessoryAction: (state: ConfiguratorState, action: { payload: {
            type: DraftControlAllowedTypes,
            price: number,
            count: number,
        },}) => {
            const currentState: ConfiguratorState = current(state);

            state.draftControl[action.payload.type].count = currentState.accessories[action.payload.type]?.price ? 1 : 0;
            state.draftControl[action.payload.type].totalPrice = action.payload.price || 0;
        },
        updateMattressPrice: (state: ConfiguratorState) => {
            const currentState: ConfiguratorState = current(state);
            const isPublicCustomerType: boolean = currentState.customer === CONST.CUSTOMER_TYPE_PUBLIC;
            const typeMattress = CONST.ACCESSORY_TYPE_MATTRESS;
            const currentStateAccessory = currentState.accessories[typeMattress];
            const standardAxisX: number = currentState.settings.standard.axisX + 300;
            const individualAxisX: number = currentState.settings.realtime.individual.axisX + 300;
            const isExtraSize: boolean = !!currentState.draftControl.extraSize.count;

            if (isPublicCustomerType) {
                const price = currentStateAccessory?.price?.filter((row: { minWidth: number, maxWidth: number,}) => {
                    const d = isExtraSize ? individualAxisX : standardAxisX;

                    return (row.minWidth || 0) <= d && d <= row.maxWidth;
                })[0]?.price;

                state.draftControl[typeMattress].count = currentStateAccessory?.price ? 1 : 0;
                state.draftControl[typeMattress].totalPrice = price || 0;
            }
        },
        updateAccessoryCount: (state: ConfiguratorState, action: { payload: {
            type: DraftControlAllowedTypes,
            price: number,
            count: number,
        },}) => {
            state.draftControl[action.payload.type].count = action.payload.count;
            state.draftControl[action.payload.type].totalPrice = action.payload.price * action.payload.count || 0;
        },
        setDraftList: (state: ConfiguratorState, action: { payload: DraftListItemType[],}) => {
            state.draftList = action.payload;
        },
        setHistory: (state: ConfiguratorState) => {
            const currentState: ConfiguratorState = current(state);

            const configuratorGridTemplate = currentState.grid[currentState.settings.direction];
            const preparedWorkspace: {[key: string]: string,} = {};

            for (const cell of Object.keys(configuratorGridTemplate)) {
                const gridItem = configuratorGridTemplate[cell];
                preparedWorkspace[cell] = gridItem?.desk?.id ? { ...gridItem } : '';
            }

            const obj = {
                orientation: currentState.settings.direction,
                calculatedWidth: currentState.settings.standard.axisX,
                calculatedHeight: currentState.settings.standard.axisY,
                customWidth: currentState.settings.individual.axisX,
                customHeight: currentState.settings.individual.axisY,
                grip: currentState.accessories.hold?.id || null,
                mattress: currentState.accessories.mattress?.id || null,
                mattressQuantity: currentState.draftControl.mattress.count,
                gripQuantity: currentState.draftControl.hold.count,
                workspace: preparedWorkspace,
            };

            // const obj = {
            //     ...action.payload,
            // };

            const updatedHistoryList = [...currentState.history.list];

            let upl = [];

            if (currentState.history.currentPosition < currentState.history.totalPosition) {
                state.history.currentPosition = currentState.history.list.length + 1;
            } else {
                state.history.currentPosition += 1;
            }

            upl = [...updatedHistoryList];
            state.history.totalPosition += 1;
            upl.push(obj);

            state.history.list = upl;
        },
        resetHistory: (state: ConfiguratorState) => {
            state.history = initialState.history;
        },
        increaseHistoryPosition: (state: ConfiguratorState) => {
            state.history.currentPosition += 1;
        },
        decreaseHistoryPosition: (state: ConfiguratorState) => {
            state.history.currentPosition -= 1;
        },
        resetStandardAxis: (state: ConfiguratorState) => {
            state.settings.standard = {
                ...initialState.settings.standard,
            };
        },
        resetIndividualAxis: (state: ConfiguratorState) => {
            state.settings.individual = {
                ...initialState.settings.individual,
            };
        },
        resetRealtimeIndividualAxis: (state: ConfiguratorState) => {
            state.settings.realtime.individual = {
                ...initialState.settings.realtime.individual,
            };
        },
        resetAccessories: (state: ConfiguratorState) => {
            state.accessories = {
                ...initialState.accessories,
            };
        },
        resetGrid: (state: ConfiguratorState) => {
            state.grid = {
                ...initialState.grid,
            };
        },
        resetDraftControl: (state: ConfiguratorState) => {
            state.draftControl = {
                ...initialState.draftControl,
            };
        },
        reset: () => {
            return {
                ...initialState,
            };
        },
        setDrawerOpen: (state: ConfiguratorState, action: { payload: boolean,}) => {
            state.temp.drawer.isOpened = action.payload;
        },
        setInitialHoldList: (state: ConfiguratorState, action: { payload: object[],}) => {
            state.temp.holdList = action.payload;
        },
        setInitialMattressList: (state: ConfiguratorState, action: { payload: object[],}) => {
            state.temp.mattressList = action.payload;
        },
    },
    extraReducers: builder => {
        // builder.addCase(setCustomerType.pending, state => {
        // });
        // builder.addCase(setCustomerType.fulfilled, (state, action) => {
        // });
        // builder.addCase(setCustomerType.rejected, (state, action) => {
        // });
    },
});

// Action creators are generated for each case reducer function
export const {
    setCustomerType,
    setLocationType,
    setDraftDirection,
    setIndividualAxis,
    setRealtimeIndividualAxis,
    setStandardAxis,
    setInitialAccessory,
    setAccessory,
    setGrid,
    setInitialGrid,
    setDraftList,
    recalculateDraft,
    recalculateDraftByGridAction,
    recalculateDraftByAccessoryAction,
    updateMattressPrice,
    updateAccessoryCount,
    updateRotation,
    updateOverlay,
    reset,
    resetStandardAxis,
    resetIndividualAxis,
    resetRealtimeIndividualAxis,
    resetAccessories,
    resetGrid,
    resetDraftControl,
    setDrawerOpen,
    setHistory,
    resetHistory,
    increaseHistoryPosition,
    decreaseHistoryPosition,
    setInitialHoldList,
    setInitialMattressList,
} = configuratorSlice.actions;
// State selector
export const configuratorSelector = (state: RootState) => state.configurator;
export const configuratorSettingsSelector = (state: RootState) => state.configurator.settings;
export const configuratorDraftDirectionSelector = (state: RootState) => state.configurator.settings.direction;
export const configuratorCustomerTypeSelector = (state: RootState) => state.configurator.customer;
export const configuratorStandardSizeSelector = (state: RootState) => state.configurator.settings.standard;
export const configuratorIndividualSizeSelector = (state: RootState) => state.configurator.settings.individual;
export const configuratorRealtimeIndividualSizeSelector = (state: RootState) => state.configurator.settings.realtime.individual;
export const configuratorMattressSelector = (state: RootState) => state.configurator.accessories.mattress;
export const configuratorHoldsSelector = (state: RootState) => state.configurator.accessories.hold;
export const configuratorGridTemplateSelector = (state: RootState) => state.configurator.grid[state.configurator.settings.direction];
export const configuratorDraftControlSelector = (state: RootState) => state.configurator.draftControl;
export const configuratorDraftListSelector = (state: RootState) => state.configurator.draftList;
export const configuratorTempDrawerState = (state: RootState) => state.configurator.temp.drawer.isOpened;
export const configuratorHistorySelector = (state: RootState) => state.configurator.history;
export const configuratorHoldListSelector = (state: RootState) => state.configurator.temp.holdList;
export const configuratorMattressListSelector = (state: RootState) => state.configurator.temp.mattressList;
export const configuratorGridCountMaxSelector = (state: RootState) => state.configurator.temp.gridCountMax;

export default configuratorSlice.reducer;
