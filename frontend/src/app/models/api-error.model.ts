export interface FieldError {
  field: string;
  message: string;
}

export interface ApiError {
  status: number;
  message: string;
  fieldErrors?: FieldError[];
  isNetwork?: boolean;
}
