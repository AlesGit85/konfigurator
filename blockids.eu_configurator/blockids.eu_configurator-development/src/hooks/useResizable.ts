import { useMemo } from 'react';
import { useSelector } from 'react-redux';
import { GridDirectionType } from '@/components/dnd/grid/grid';
import { GRID_CONFIG } from '@/lib/grid';
import { convert } from '@/lib/utils';
import { configuratorIndividualSizeSelector, configuratorStandardSizeSelector } from '@/redux/slices/configuratorSlice';

export interface IUseResizableProps {
    direction: GridDirectionType
}

export const useResizable = (
    {
        direction,
    }: IUseResizableProps,
): {
    minAllowedWidth: number,
    minAllowedWidthCm: number,
    minAllowedHeight: number,
    minAllowedHeightCm: number,
} => {
    const configuratorStandardSize = useSelector(configuratorStandardSizeSelector);

    const gridConfigDirection = GRID_CONFIG[direction];

    const pixelToCmY = gridConfigDirection.pixelToCmY;
    const pixelToCmX = gridConfigDirection.pixelToCmX;

    const configuratorStandardSizeAxisX = configuratorStandardSize.axisX;
    const configuratorStandardSizeAxisY = configuratorStandardSize.axisY;

    const minAllowedWidth: number = useMemo(
        () => configuratorStandardSizeAxisX ? convert(configuratorStandardSizeAxisX + gridConfigDirection.cmMinCutX, pixelToCmX) : 2,
        [configuratorStandardSizeAxisX, gridConfigDirection.cmMinCutX, pixelToCmX],
    );
    const minAllowedHeight: number = useMemo(
        () => configuratorStandardSizeAxisY ? convert(configuratorStandardSizeAxisY + gridConfigDirection.cmMinCutY, pixelToCmY) : 2,
        [configuratorStandardSizeAxisY, gridConfigDirection.cmMinCutY, pixelToCmY],
    );

    const minAllowedWidthCm: number = useMemo(
        () => configuratorStandardSizeAxisX ? configuratorStandardSizeAxisX + gridConfigDirection.cmMinCutX : 2,
        [configuratorStandardSizeAxisX, gridConfigDirection.cmMinCutX, pixelToCmX],
    );
    const minAllowedHeightCm: number = useMemo(
        () => configuratorStandardSizeAxisY ? configuratorStandardSizeAxisY + gridConfigDirection.cmMinCutY : 2,
        [configuratorStandardSizeAxisY, gridConfigDirection.cmMinCutY, pixelToCmY],
    );

    return {
        minAllowedHeight,
        minAllowedHeightCm,
        minAllowedWidth,
        minAllowedWidthCm,
    };
};
