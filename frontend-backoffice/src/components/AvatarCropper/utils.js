export const createImage = async (url) => {
  // Si l'URL est une URL S3 présignée, utiliser le proxy backend avec fetch pour l'authentification
  if (url && (url.includes('s3.amazonaws.com') || url.includes('amazonaws.com') || url.includes('s3.'))) {
    try {
      const apiBaseUrl = process.env.REACT_APP_API_URL || 'http://localhost:8050';
      const proxyUrl = `${apiBaseUrl}/api/admin/media/proxy?url=${encodeURIComponent(url)}`;
      
      // Récupérer le token depuis localStorage
      const token = localStorage.getItem('token');
      const headers = {};
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
      
      // Charger l'image via fetch avec authentification
      const response = await fetch(proxyUrl, { headers });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      // Créer un blob à partir de la réponse
      const blob = await response.blob();
      const blobUrl = URL.createObjectURL(blob);
      
      // Créer l'image à partir du blob URL
      return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => {
          URL.revokeObjectURL(blobUrl); // Nettoyer le blob URL après chargement
          resolve(image);
        };
        image.onerror = (error) => {
          URL.revokeObjectURL(blobUrl);
          reject(error);
        };
        image.src = blobUrl;
      });
    } catch (error) {
      console.error('Error loading image via proxy:', error);
      throw error;
    }
  } else {
    // Pour les autres images, utiliser la méthode standard
    return new Promise((resolve, reject) => {
      const image = new Image();
      image.crossOrigin = 'anonymous';
      image.onload = () => resolve(image);
      image.onerror = reject;
      image.src = url;
    });
  }
};

export function getRadianAngle(degreeValue) {
  return (degreeValue * Math.PI) / 180;
}

export function rotateSize(width, height, rotation) {
  const rotRad = getRadianAngle(rotation);
  return {
    width: Math.abs(Math.cos(rotRad) * width) + Math.abs(Math.sin(rotRad) * height),
    height: Math.abs(Math.sin(rotRad) * width) + Math.abs(Math.cos(rotRad) * height),
  };
}

export async function getCroppedImg(imageSrc, pixelCrop, rotation = 0) {
  const image = await createImage(imageSrc);
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');

  if (!ctx) {
    throw new Error('No 2d context');
  }

  const maxSize = Math.max(image.width, image.height);
  const safeArea = 2 * ((maxSize / 2) * Math.sqrt(2));

  canvas.width = safeArea;
  canvas.height = safeArea;

  ctx.translate(safeArea / 2, safeArea / 2);
  ctx.rotate(getRadianAngle(rotation));
  ctx.translate(-safeArea / 2, -safeArea / 2);

  ctx.drawImage(
    image,
    safeArea / 2 - image.width * 0.5,
    safeArea / 2 - image.height * 0.5
  );

  const data = ctx.getImageData(0, 0, safeArea, safeArea);

  canvas.width = pixelCrop.width;
  canvas.height = pixelCrop.height;

  ctx.putImageData(
    data,
    Math.round(0 - safeArea / 2 + image.width * 0.5 - pixelCrop.x),
    Math.round(0 - safeArea / 2 + image.height * 0.5 - pixelCrop.y)
  );

  return new Promise((resolve) => {
    canvas.toBlob((blob) => {
      if (!blob) {
        resolve(null);
        return;
      }
      // Retourner le blob pour pouvoir l'uploader
      resolve(blob);
    }, 'image/png');
  });
}

export function blobToDataURL(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}

