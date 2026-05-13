using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace ozon.Models
{
    public class ReportHistory
    {
        public int Id { get; set; }

        public string ReportName { get; set; }  
        public string ReportTypeId { get; set; }       
        public string FileName { get; set; }        
        public string FilePath { get; set; }          
        public long FileSize { get; set; }           
    }
}
