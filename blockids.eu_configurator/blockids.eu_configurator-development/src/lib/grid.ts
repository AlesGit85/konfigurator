type GridConfigContentType = {
    axisX: string[],
    axisY: number[],
    defaultWidth: number,
    defaultHeight: number,
    pixelToCmX: number,
    pixelToCmY: number,
    cmToPixelX: number,
    cmToPixelY: number,
    cmBaseX: number,
    cmBaseY: number,
    cmMaxX: number,
    cmMaxY: number,
    cmMinCutX: number,
    cmMinCutY: number,
    maxAllowed: {
        [key: string]: number,
    },
    mattress: {
        [key: string]: number,
    },
    baseRowX: string[],
}

type GridConfigType = {
    [key: string]: GridConfigContentType,
}

export const GRID_CONFIG: GridConfigType = {
    vertical: {
        axisX: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
        axisY: [1, 2, 3],
        defaultWidth: 1063,
        defaultHeight: 596,
        pixelToCmX: 1063 / 624,
        pixelToCmY: 596 / 354,
        cmToPixelX: 624 / 1063,
        cmToPixelY: 354 / 596,
        cmBaseX: 78,
        cmBaseY: 118,
        cmMaxX: 624,
        cmMaxY: 354,
        cmMinCutX: -30,
        cmMinCutY: -40,
        maxAllowed: {
            family: 24,
            public: 16,
        },
        mattress: {
            family: 132,
            public: 132,
        },
        baseRowX: ['A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1'],
    },
    horizontal: {
        axisX: ['A', 'B', 'C', 'D', 'E'],
        axisY: [1, 2, 3, 4],
        defaultWidth: 994,
        defaultHeight: 531,
        pixelToCmX: 994 / 590,
        pixelToCmY: 531 / 312,
        cmToPixelX: 590 / 994,
        cmToPixelY: 312 / 531,
        cmBaseX: 118,
        cmBaseY: 78,
        cmMaxX: 590,
        cmMaxY: 312,
        cmMinCutX: -40,
        cmMinCutY: -30,
        maxAllowed: {
            family: 20,
            public: 20,
        },
        mattress: {
            family: 132,
            public: 198,
        },
        baseRowX: ['A1', 'B1', 'C1', 'D1', 'E1'],
    },
};

