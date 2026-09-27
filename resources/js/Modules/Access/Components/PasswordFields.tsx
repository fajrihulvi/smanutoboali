import { Check, Copy, Wand2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/Components/input-error';
import PasswordInput from '@/Components/password-input';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import { generatePassword } from '@/lib/password';

type Props = {
    password: string;
    confirmation: string;
    onChange: (password: string, confirmation: string) => void;
    error?: string;
    label?: string;
};

/**
 * Password + konfirmasi dengan tombol "Buat acak" dan salin.
 */
export function PasswordFields({
    password,
    confirmation,
    onChange,
    error,
    label = 'Password',
}: Props) {
    const [copiedText, copy] = useClipboard();
    const [generated, setGenerated] = useState(false);

    const generate = () => {
        const value = generatePassword();
        onChange(value, value);
        setGenerated(true);
    };

    return (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <div className="flex items-center justify-between gap-2">
                    <Label htmlFor="password">{label}</Label>
                    <div className="flex gap-1">
                        {generated && password && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => copy(password)}
                            >
                                {copiedText === password ? <Check /> : <Copy />}
                                {copiedText === password ? 'Disalin' : 'Salin'}
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={generate}
                        >
                            <Wand2 />
                            Buat acak
                        </Button>
                    </div>
                </div>
                <PasswordInput
                    id="password"
                    value={password}
                    onChange={(event) => {
                        setGenerated(false);
                        onChange(event.target.value, confirmation);
                    }}
                    autoComplete="new-password"
                    aria-invalid={!!error}
                />
                {generated && (
                    <p className="text-xs text-slate-500">
                        Salin password ini dan berikan kepada pengguna. Password
                        tidak akan ditampilkan lagi setelah disimpan.
                    </p>
                )}
                <InputError message={error} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Ulangi password</Label>
                <PasswordInput
                    id="password_confirmation"
                    value={confirmation}
                    onChange={(event) => onChange(password, event.target.value)}
                    autoComplete="new-password"
                />
            </div>
        </div>
    );
}
