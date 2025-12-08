namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    use HasFactory;

    protected $table = 'scheduled_messages';

    protected $fillable = [
        'message_content',
        'scheduled_time',
        'recipient',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
    ];
}