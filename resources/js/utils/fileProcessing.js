import { PDFDocument } from 'pdf-lib';
import JSZip from 'jszip';

export const getPageCount = async (file) => {
    if (file.type.startsWith('image/')) {
        return 1;
    }

    if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
        try {
            const arrayBuffer = await file.arrayBuffer();
            const pdfDoc = await PDFDocument.load(arrayBuffer, { ignoreEncryption: true });
            return pdfDoc.getPageCount();
        } catch (e) {
            console.error(`Failed to count pages for PDF ${file.name}:`, e);
            return 'All';
        }
    }

    if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || file.name.toLowerCase().endsWith('.docx')) {
        try {
            const arrayBuffer = await file.arrayBuffer();
            const zip = await JSZip.loadAsync(arrayBuffer);
            const appXmlFile = zip.file('docProps/app.xml');
            if (appXmlFile) {
                const appXmlContent = await appXmlFile.async('string');
                const match = /<Pages>(\d+)<\/Pages>/.exec(appXmlContent);
                if (match && parseInt(match[1], 10) > 0) {
                    return parseInt(match[1], 10);
                }
            }
            return 'All';
        } catch (e) {
            console.error(`Failed to count pages for DOCX ${file.name}:`, e);
            return 'All';
        }
    }

    return 'All';
};

export const toBase64 = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = error => reject(error);
});