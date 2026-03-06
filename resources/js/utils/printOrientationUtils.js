// Utility to load print orientations from local JSON
import orientations from '../data/print-orientations.json';

export async function getPrintOrientations() {
    return orientations;
}
