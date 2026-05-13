using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ozon.Models
{
    public class UserAction
    {
        public int Id { get; set; }
        public int UserId { get; set; }          
        public string ActionTypeId{ get; set; }         
        public DateTime ActionDate { get; set; } = DateTime.Now; // Дата и время действия

    }

}
