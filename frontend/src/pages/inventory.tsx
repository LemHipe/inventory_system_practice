import { useState, useMemo } from 'react';
import {
  useReactTable,
  getCoreRowModel,
  getPaginationRowModel,
  ColumnDef,
} from '@tanstack/react-table';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Trash2, Search, X, Upload, History, TrendingUp, TrendingDown } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuth } from '@/contexts/auth-context';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { DataTablePagination } from '@/components/ui/data-table-pagination';

interface InventoryItem {
  id: string;
  item_code: string;
  product_id: string;
  product_name: string;
  description: string;
  quantity: number;
  price: number;
  category: string;
  created_at: string;
  updated_at: string | null;
}

interface InventoryResponse {
  success: boolean;
  data: InventoryItem[];
  categories: string[];
}

export function InventoryPage() {
  const { user } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [editOpen, setEditOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<InventoryItem | null>(null);
  const [editQuantity, setEditQuantity] = useState('');
  const [editPrice, setEditPrice] = useState('');
  const [priceChangeReason, setPriceChangeReason] = useState('');
  const [priceHistoryOpen, setPriceHistoryOpen] = useState(false);
  const [priceHistoryItem, setPriceHistoryItem] = useState<InventoryItem | null>(null);
  const [csvUploadOpen, setCsvUploadOpen] = useState(false);
  const [csvFile, setCsvFile] = useState<File | null>(null);
  const [skippedRows, setSkippedRows] = useState<any[]>([]);
  const [showSkippedDialog, setShowSkippedDialog] = useState(false);
  const [formData, setFormData] = useState({
    product_name: '',
    description: '',
    quantity: '',
    price: '',
    category: '',
  });

  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery<InventoryResponse>({
    queryKey: ['inventory'],
    queryFn: async () => {
      const response = await api.get('/inventory');
      return response.data;
    },
    refetchInterval: 5000,
  });

  // Client-side filtering with TanStack Query data
  const filteredData = useMemo(() => {
    if (!data?.data) return [];
    
    return data.data.filter((item) => {
      const matchesSearch = searchQuery === '' || 
        item.product_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.item_code?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        item.description?.toLowerCase().includes(searchQuery.toLowerCase());
      
      const matchesCategory = selectedCategory === '' || item.category === selectedCategory;
      
      return matchesSearch && matchesCategory;
    });
  }, [data?.data, searchQuery, selectedCategory]);

  const categories = data?.categories || [];

  // TanStack Table columns definition
  const columns = useMemo<ColumnDef<InventoryItem>[]>(
    () => [
      {
        accessorKey: 'item_code',
        header: 'Item Code',
      },
      {
        accessorKey: 'product_name',
        header: 'Product Name',
      },
      {
        accessorKey: 'category',
        header: 'Category',
      },
      {
        accessorKey: 'quantity',
        header: 'Quantity',
      },
      {
        accessorKey: 'price',
        header: 'Price',
      },
      {
        id: 'actions',
        header: 'Actions',
      },
    ],
    []
  );

  // TanStack Table instance
  const table = useReactTable({
    data: filteredData,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
      pagination: {
        pageSize: 10,
      },
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      const response = await api.post('/inventory', {
        ...data,
        quantity: parseInt(data.quantity),
        price: parseFloat(data.price),
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
      toast.success('Item created successfully');
      setIsOpen(false);
      setFormData({ product_name: '', description: '', quantity: '', price: '', category: '' });
    },
    onError: (err: any) => {
      const errors = err?.response?.data?.errors;
      if (errors?.product_name) {
        toast.error(errors.product_name[0]);
      } else {
        toast.error(err?.response?.data?.message || 'Failed to create item');
      }
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: string) => {
      await api.delete(`/inventory/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
      toast.success('Item deleted successfully');
    },
    onError: (err: any) => {
      const status = err?.response?.status;
      if (status === 403) {
        toast.error('You are not allowed to delete items');
        return;
      }
      toast.error('Failed to delete item');
    },
  });

  const updateItemMutation = useMutation({
    mutationFn: async (payload: { id: string; quantity: number; price?: number; price_change_reason?: string }) => {
      const data: any = { quantity: payload.quantity };
      if (payload.price !== undefined) {
        data.price = payload.price;
        data.price_change_reason = payload.price_change_reason;
      }
      const response = await api.put(`/inventory/${payload.id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
      queryClient.invalidateQueries({ queryKey: ['activity-logs'] });
      toast.success('Item updated successfully');
      setEditOpen(false);
      setEditingItem(null);
      setEditQuantity('');
      setEditPrice('');
      setPriceChangeReason('');
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Failed to update item';
      toast.error(message);
    },
  });

  const { data: priceHistoryData, isLoading: priceHistoryLoading } = useQuery({
    queryKey: ['price-history', priceHistoryItem?.id],
    queryFn: async () => {
      if (!priceHistoryItem) return null;
      const response = await api.get(`/inventory/${priceHistoryItem.id}/price-history`);
      return response.data;
    },
    enabled: !!priceHistoryItem && priceHistoryOpen,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(formData);
  };

  const handleEditItem = (item: InventoryItem) => {
    setEditingItem(item);
    setEditQuantity(String(item.quantity));
    setEditPrice(String(item.price));
    setPriceChangeReason('');
    setEditOpen(true);
  };

  const handleViewPriceHistory = (item: InventoryItem) => {
    setPriceHistoryItem(item);
    setPriceHistoryOpen(true);
  };

  const handleUpdateItem = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingItem) return;
    const payload: any = {
      id: editingItem.id,
      quantity: parseInt(editQuantity, 10),
    };
    // Only include price if admin and price changed
    if (user?.role === 'admin' && parseFloat(editPrice) !== editingItem.price) {
      payload.price = parseFloat(editPrice);
      payload.price_change_reason = priceChangeReason;
    }
    updateItemMutation.mutate(payload);
  };

  const csvUploadMutation = useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData();
      formData.append('file', file);
      const response = await api.post('/inventory/upload-csv', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
      queryClient.invalidateQueries({ queryKey: ['activity-logs'] });
      toast.success(`${data.created_count} items imported successfully`);
      if (data.errors?.length > 0) {
        data.errors.slice(0, 3).forEach((err: string) => toast.error(err));
      }
      // Handle skipped duplicates
      if (data.skipped?.length > 0) {
        setSkippedRows(data.skipped);
        toast.warning(`${data.skipped_count} duplicate(s) skipped - click to view details`, {
          action: {
            label: 'View',
            onClick: () => setShowSkippedDialog(true),
          },
          duration: 10000,
        });
      }
      setCsvUploadOpen(false);
      setCsvFile(null);
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Failed to upload CSV';
      toast.error(message);
    },
  });

  const handleCsvUpload = (e: React.FormEvent) => {
    e.preventDefault();
    if (!csvFile) return;
    csvUploadMutation.mutate(csvFile);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Inventory</h2>
          <p className="text-muted-foreground">Manage your inventory items</p>
        </div>
        <div className="flex gap-2">
          <Dialog open={csvUploadOpen} onOpenChange={setCsvUploadOpen}>
            <DialogTrigger asChild>
              <Button variant="outline">
                <Upload className="mr-2 h-4 w-4" />
                Mass Upload CSV
              </Button>
            </DialogTrigger>
            <DialogContent>
              <form onSubmit={handleCsvUpload}>
                <DialogHeader>
                  <DialogTitle>Upload CSV</DialogTitle>
                  <DialogDescription>
                    Upload a CSV file to bulk import inventory items.
                    Required columns: product_name, category, quantity, price.
                    Optional: item_code, description.
                  </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                  <div className="grid gap-2">
                    <Label htmlFor="csv_file">CSV File</Label>
                    <Input
                      id="csv_file"
                      type="file"
                      accept=".csv,.xlsx,.xls"
                      onChange={(e) => setCsvFile(e.target.files?.[0] || null)}
                      required
                    />
                  </div>
                  {csvFile && (
                    <p className="text-sm text-muted-foreground">
                      Selected: {csvFile.name}
                    </p>
                  )}
                </div>
                <DialogFooter>
                  <Button type="submit" disabled={csvUploadMutation.isPending || !csvFile}>
                    {csvUploadMutation.isPending ? 'Uploading...' : 'Upload'}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
          <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                Add Item
              </Button>
            </DialogTrigger>
          <DialogContent>
            <form onSubmit={handleSubmit}>
              <DialogHeader>
                <DialogTitle>Add New Item</DialogTitle>
                <DialogDescription>Add a new item to your inventory</DialogDescription>
              </DialogHeader>
              <div className="grid gap-4 py-4">
                <div className="grid gap-2">
                  <Label htmlFor="product_name">Product Name</Label>
                  <Input
                    id="product_name"
                    value={formData.product_name}
                    onChange={(e) => setFormData({ ...formData, product_name: e.target.value })}
                    required
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="description">Description</Label>
                  <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="grid gap-2">
                    <Label htmlFor="quantity">Quantity</Label>
                    <Input
                      id="quantity"
                      type="number"
                      min="0"
                      value={formData.quantity}
                      onChange={(e) => setFormData({ ...formData, quantity: e.target.value })}
                      required
                    />
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="price">Price</Label>
                    <Input
                      id="price"
                      type="number"
                      min="0"
                      step="0.01"
                      value={formData.price}
                      onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                      required
                    />
                  </div>
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="category">Category</Label>
                  <Input
                    id="category"
                    value={formData.category}
                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                    required
                  />
                </div>
              </div>
              <DialogFooter>
                <Button type="submit" disabled={createMutation.isPending}>
                  {createMutation.isPending ? 'Creating...' : 'Create'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
        </div>
      </div>

      <Dialog open={editOpen} onOpenChange={(open) => {
        setEditOpen(open);
        if (!open) {
          setEditingItem(null);
          setEditQuantity('');
          setEditPrice('');
          setPriceChangeReason('');
        }
      }}>
        <DialogContent>
          <form onSubmit={handleUpdateItem}>
            <DialogHeader>
              <DialogTitle>Edit Item</DialogTitle>
              <DialogDescription>
                Update {editingItem?.product_name}
              </DialogDescription>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="grid gap-2">
                <Label htmlFor="edit_quantity">Quantity</Label>
                <Input
                  id="edit_quantity"
                  type="number"
                  min="0"
                  value={editQuantity}
                  onChange={(e) => setEditQuantity(e.target.value)}
                  required
                />
              </div>
              {user?.role === 'admin' && (
                <>
                  <div className="grid gap-2">
                    <Label htmlFor="edit_price">Price</Label>
                    <Input
                      id="edit_price"
                      type="number"
                      min="0"
                      step="0.01"
                      value={editPrice}
                      onChange={(e) => setEditPrice(e.target.value)}
                      required
                    />
                  </div>
                  {parseFloat(editPrice) !== editingItem?.price && (
                    <div className="grid gap-2">
                      <Label htmlFor="price_reason">Reason for Price Change (optional)</Label>
                      <Input
                        id="price_reason"
                        placeholder="e.g., Supplier price increase"
                        value={priceChangeReason}
                        onChange={(e) => setPriceChangeReason(e.target.value)}
                      />
                    </div>
                  )}
                </>
              )}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={updateItemMutation.isPending}>
                {updateItemMutation.isPending ? 'Saving...' : 'Save'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Skipped Rows Dialog */}
      <Dialog open={showSkippedDialog} onOpenChange={setShowSkippedDialog}>
        <DialogContent className="max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
          <DialogHeader>
            <DialogTitle>Skipped Rows ({skippedRows.length})</DialogTitle>
            <DialogDescription>
              The following rows were skipped because they already exist in the database.
            </DialogDescription>
          </DialogHeader>
          <div className="flex-1 overflow-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-16">Row</TableHead>
                  <TableHead>Product Name</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Reason</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {skippedRows.map((row, idx) => (
                  <TableRow key={idx}>
                    <TableCell className="font-mono">{row.row}</TableCell>
                    <TableCell>{row.product_name}</TableCell>
                    <TableCell>{row.category}</TableCell>
                    <TableCell className="text-sm text-muted-foreground">{row.reason}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => {
              const csvContent = "row,product_name,category,quantity,price,item_code,description,reason\n" +
                skippedRows.map(r => `${r.row},"${r.product_name}","${r.category}",${r.quantity},${r.price},"${r.item_code}","${r.description}","${r.reason}"`).join("\n");
              const blob = new Blob([csvContent], { type: 'text/csv' });
              const url = URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = 'skipped_rows.csv';
              a.click();
              URL.revokeObjectURL(url);
            }}>
              Download Skipped Rows CSV
            </Button>
            <Button onClick={() => setShowSkippedDialog(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Price History Dialog */}
      <Dialog open={priceHistoryOpen} onOpenChange={(open) => {
        setPriceHistoryOpen(open);
        if (!open) setPriceHistoryItem(null);
      }}>
        <DialogContent className="max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
          <DialogHeader>
            <DialogTitle>Price History</DialogTitle>
            <DialogDescription>
              {priceHistoryItem?.product_name} - Current Price: ₱{Number(priceHistoryItem?.price || 0).toFixed(2)}
            </DialogDescription>
          </DialogHeader>
          <div className="flex-1 overflow-auto">
            {priceHistoryLoading ? (
              <div className="flex items-center justify-center py-8">
                <span className="text-muted-foreground">Loading...</span>
              </div>
            ) : priceHistoryData?.data?.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-8 text-center">
                <History className="h-12 w-12 text-muted-foreground/50 mb-2" />
                <p className="text-muted-foreground">No price changes recorded</p>
                <p className="text-xs text-muted-foreground/70">Price history will appear here when changes are made</p>
              </div>
            ) : (
              <div className="space-y-3">
                {priceHistoryData?.data?.map((history: any) => {
                  const isIncrease = history.new_price > history.old_price;
                  const diff = Math.abs(history.new_price - history.old_price);
                  const percentChange = ((diff / history.old_price) * 100).toFixed(1);
                  
                  return (
                    <div key={history.id} className="rounded-lg border p-3">
                      <div className="flex items-start justify-between">
                        <div className="flex items-center gap-2">
                          {isIncrease ? (
                            <div className="rounded-full bg-red-100 p-1.5 dark:bg-red-900/30">
                              <TrendingUp className="h-4 w-4 text-red-600 dark:text-red-400" />
                            </div>
                          ) : (
                            <div className="rounded-full bg-green-100 p-1.5 dark:bg-green-900/30">
                              <TrendingDown className="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                          )}
                          <div>
                            <div className="flex items-center gap-2">
                              <span className="font-medium">₱{Number(history.old_price).toFixed(2)}</span>
                              <span className="text-muted-foreground">→</span>
                              <span className="font-medium">₱{Number(history.new_price).toFixed(2)}</span>
                              <Badge variant={isIncrease ? "destructive" : "default"} className="text-xs">
                                {isIncrease ? '+' : '-'}{percentChange}%
                              </Badge>
                            </div>
                            {history.reason && (
                              <p className="text-xs text-muted-foreground mt-1">{history.reason}</p>
                            )}
                          </div>
                        </div>
                      </div>
                      <div className="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                        <span>by {history.changed_by_user?.name || 'Unknown'}</span>
                        <span>{new Date(history.created_at).toLocaleDateString('en-US', { 
                          month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' 
                        })}</span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
          <DialogFooter>
            <Button onClick={() => setPriceHistoryOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col gap-4 md:flex-row md:items-end">
            <div className="flex-1">
              <Label htmlFor="search" className="mb-2 block text-sm font-medium">Search</Label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  id="search"
                  placeholder="Search by item code, name, or description..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9"
                />
                {searchQuery && (
                  <Button
                    variant="ghost"
                    size="icon"
                    className="absolute right-1 top-1/2 h-6 w-6 -translate-y-1/2"
                    onClick={() => setSearchQuery('')}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>
            </div>
            <div className="w-full md:w-48">
              <Label htmlFor="category" className="mb-2 block text-sm font-medium">Category</Label>
              <select
                id="category"
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                <option value="">All Categories</option>
                {categories.map((cat) => (
                  <option key={cat} value={cat}>{cat}</option>
                ))}
              </select>
            </div>
            {(searchQuery || selectedCategory) && (
              <Button
                variant="outline"
                onClick={() => {
                  setSearchQuery('');
                  setSelectedCategory('');
                }}
              >
                <X className="mr-2 h-4 w-4" />
                Clear Filters
              </Button>
            )}
          </div>
          {(searchQuery || selectedCategory) && (
            <div className="mt-4 flex items-center gap-2">
              <span className="text-sm text-muted-foreground">Active filters:</span>
              {searchQuery && (
                <Badge variant="secondary" className="gap-1">
                  Search: {searchQuery}
                  <X className="h-3 w-3 cursor-pointer" onClick={() => setSearchQuery('')} />
                </Badge>
              )}
              {selectedCategory && (
                <Badge variant="secondary" className="gap-1">
                  Category: {selectedCategory}
                  <X className="h-3 w-3 cursor-pointer" onClick={() => setSelectedCategory('')} />
                </Badge>
              )}
              <span className="text-sm text-muted-foreground ml-2">
                ({filteredData.length} of {data?.data?.length || 0} items)
              </span>
            </div>
          )}
        </CardContent>
      </Card>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Item Code</TableHead>
              <TableHead>Product Name</TableHead>
              <TableHead>Category</TableHead>
              <TableHead className="text-right">Quantity</TableHead>
              <TableHead className="text-right">Price</TableHead>
              <TableHead className="w-[100px]">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center">
                  Loading...
                </TableCell>
              </TableRow>
            ) : table.getRowModel().rows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground">
                  {data?.data?.length === 0 ? 'No inventory items found' : 'No items match your filters'}
                </TableCell>
              </TableRow>
            ) : (
              table.getRowModel().rows.map((row) => {
                const item = row.original;
                const isLowStock = item.quantity <= 20;
                return (
                  <TableRow 
                    key={item.id}
                    className={cn(
                      isLowStock && "bg-red-50 hover:bg-red-100 dark:bg-red-950/30 dark:hover:bg-red-950/50"
                    )}
                  >
                    <TableCell>
                      <code className="rounded bg-muted px-2 py-1 text-xs font-mono">
                        {item.item_code || '-'}
                      </code>
                    </TableCell>
                    <TableCell className="font-medium">
                      <div className="flex items-center gap-2">
                        {item.product_name}
                        {isLowStock && (
                          <span className="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-semibold text-white">
                            LOW STOCK
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">{item.category}</Badge>
                    </TableCell>
                    <TableCell className={cn(
                      "text-right font-medium",
                      isLowStock && "text-red-600 dark:text-red-400"
                    )}>
                      {item.quantity}
                    </TableCell>
                    <TableCell className="text-right">₱{Number(item.price).toFixed(2)}</TableCell>
                    <TableCell>
                      <div className="flex items-center justify-end gap-1">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleViewPriceHistory(item)}
                          title="Price History"
                        >
                          <History className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEditItem(item)}
                        >
                          Edit
                        </Button>

                        {user?.role === 'admin' && (
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => deleteMutation.mutate(item.id)}
                            disabled={deleteMutation.isPending}
                          >
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>
      <DataTablePagination table={table} />
    </div>
  );
}
