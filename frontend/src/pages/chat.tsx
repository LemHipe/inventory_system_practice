import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Send, Plus, MessageSquare } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useAuth } from '@/contexts/auth-context';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

interface Chat {
  id: string;
  name: string;
  created_by: number;
  created_at: string;
}

interface Message {
  id: string;
  chat_id: string;
  sender_id: number;
  content: string;
  created_at: string;
}

interface ChatsResponse {
  success: boolean;
  data: Chat[];
}

interface MessagesResponse {
  success: boolean;
  data: Message[];
}

export function ChatPage() {
  const { user } = useAuth();
  const [selectedChat, setSelectedChat] = useState<string | null>(null);
  const [message, setMessage] = useState('');
  const [isOpen, setIsOpen] = useState(false);
  const [chatName, setChatName] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const queryClient = useQueryClient();

  const { data: chats, isLoading: chatsLoading } = useQuery<ChatsResponse>({
    queryKey: ['chats'],
    queryFn: async () => {
      const response = await api.get('/chats');
      return response.data;
    },
  });

  const { data: messages, isLoading: messagesLoading } = useQuery<MessagesResponse>({
    queryKey: ['messages', selectedChat],
    queryFn: async () => {
      const response = await api.get(`/chats/${selectedChat}/messages`);
      return response.data;
    },
    enabled: !!selectedChat,
    refetchInterval: 3000,
  });

  const createChatMutation = useMutation({
    mutationFn: async (name: string) => {
      const response = await api.post('/chats', { name });
      return response.data;
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['chats'] });
      toast.success('Chat created');
      setIsOpen(false);
      setChatName('');
      setSelectedChat(data.data.id);
    },
    onError: () => {
      toast.error('Failed to create chat');
    },
  });

  const sendMessageMutation = useMutation({
    mutationFn: async ({ chatId, content }: { chatId: string; content: string }) => {
      const response = await api.post(`/chats/${chatId}/messages`, { content });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['messages', selectedChat] });
      setMessage('');
    },
    onError: () => {
      toast.error('Failed to send message');
    },
  });

  const handleSendMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!message.trim() || !selectedChat) return;
    sendMessageMutation.mutate({ chatId: selectedChat, content: message });
  };

  const handleCreateChat = (e: React.FormEvent) => {
    e.preventDefault();
    if (!chatName.trim()) return;
    createChatMutation.mutate(chatName);
  };

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  return (
    <div className="flex h-[calc(100vh-8rem)] gap-4">
      {/* Chat list */}
      <div className="w-64 rounded-md border">
        <div className="flex items-center justify-between border-b p-3">
          <h3 className="font-semibold">Chats</h3>
          <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
              <Button size="icon" variant="ghost">
                <Plus className="h-4 w-4" />
              </Button>
            </DialogTrigger>
            <DialogContent>
              <form onSubmit={handleCreateChat}>
                <DialogHeader>
                  <DialogTitle>New Chat</DialogTitle>
                  <DialogDescription>Create a new chat room</DialogDescription>
                </DialogHeader>
                <div className="py-4">
                  <Label htmlFor="chat-name">Chat Name</Label>
                  <Input
                    id="chat-name"
                    value={chatName}
                    onChange={(e) => setChatName(e.target.value)}
                    placeholder="General"
                    className="mt-2"
                    required
                  />
                </div>
                <DialogFooter>
                  <Button type="submit" disabled={createChatMutation.isPending}>
                    {createChatMutation.isPending ? 'Creating...' : 'Create'}
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
        <ScrollArea className="h-[calc(100%-3.5rem)]">
          {chatsLoading ? (
            <div className="p-3 text-sm text-muted-foreground">Loading...</div>
          ) : chats?.data?.length === 0 ? (
            <div className="p-3 text-sm text-muted-foreground">No chats yet</div>
          ) : (
            chats?.data?.map((chat) => (
              <button
                key={chat.id}
                onClick={() => setSelectedChat(chat.id)}
                className={cn(
                  'flex w-full items-center gap-2 p-3 text-left text-sm transition-colors hover:bg-muted',
                  selectedChat === chat.id && 'bg-muted'
                )}
              >
                <MessageSquare className="h-4 w-4" />
                {chat.name}
              </button>
            ))
          )}
        </ScrollArea>
      </div>

      {/* Messages */}
      <div className="flex flex-1 flex-col rounded-md border">
        {selectedChat ? (
          <>
            <ScrollArea className="flex-1 p-4">
              {messagesLoading ? (
                <div className="text-sm text-muted-foreground">Loading messages...</div>
              ) : messages?.data?.length === 0 ? (
                <div className="text-sm text-muted-foreground">No messages yet</div>
              ) : (
                <div className="space-y-4">
                  {messages?.data?.map((msg) => (
                    <div
                      key={msg.id}
                      className={cn(
                        'flex',
                        msg.sender_id === user?.id ? 'justify-end' : 'justify-start'
                      )}
                    >
                      <div
                        className={cn(
                          'max-w-[70%] rounded-lg px-4 py-2',
                          msg.sender_id === user?.id
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted'
                        )}
                      >
                        <p className="text-sm">{msg.content}</p>
                        <p className="mt-1 text-xs opacity-70">{msg.created_at}</p>
                      </div>
                    </div>
                  ))}
                  <div ref={messagesEndRef} />
                </div>
              )}
            </ScrollArea>
            <form onSubmit={handleSendMessage} className="flex gap-2 border-t p-4">
              <Input
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                placeholder="Type a message..."
                className="flex-1"
              />
              <Button type="submit" size="icon" disabled={sendMessageMutation.isPending}>
                <Send className="h-4 w-4" />
              </Button>
            </form>
          </>
        ) : (
          <div className="flex flex-1 items-center justify-center text-muted-foreground">
            Select a chat to start messaging
          </div>
        )}
      </div>
    </div>
  );
}
