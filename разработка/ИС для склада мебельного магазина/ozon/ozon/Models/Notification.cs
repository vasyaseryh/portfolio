using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ozon.Models
{
    public class Notification
    {
        public int Id { get; set; }
        public string TypeId { get; set; } 
        public bool IsRead { get; set; }
        public DateTime CreatedDate { get; set; }
 
    }
}
