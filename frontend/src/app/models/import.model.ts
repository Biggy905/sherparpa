export interface ImportRowError {
  row: number;
  message: string;
}

export interface ImportProductsStatus {
  status: 'pending' | 'processing' | 'completed' | 'failed';
  total: number;
  processed: number;
  failed: number;
  progress_percent: number;
  errors: ImportRowError[];
}

export interface ImportImagesStatus {
  pending: number;
  processing: number;
  completed: number;
  failed: number;
  total: number;
}

export interface ImportStatus {
  job_id: string;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  stage: 'products' | 'images' | 'done' | 'failed';
  products: ImportProductsStatus;
  images: ImportImagesStatus;
}

export interface ImportStartResponse {
  job_id: string;
  status: 'pending';
}
